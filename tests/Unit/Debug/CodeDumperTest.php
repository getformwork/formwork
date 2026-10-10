<?php

namespace Formwork\Tests\Unit\Debug;

use Formwork\Debug\CodeDumper;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Debug\Fixtures\CodeDumperTarget;
use Formwork\Utils\Exceptions\FileNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(CodeDumper::class)]
final class CodeDumperTest extends TestCase
{
    private const string SAMPLE = __DIR__ . '/Fixtures/code/sample.php';

    private const string HOSTILE = __DIR__ . '/Fixtures/code/hostile.php';

    protected function setUp(): void
    {
        parent::setUp();
        (new ReflectionProperty(CodeDumper::class, 'stylesDumped'))->setValue(null, false);
    }

    public function testLinesAroundTheTargetAreShown(): void
    {
        $html = $this->dumpLine(self::SAMPLE, 12, 2);

        $this->assertSame([10, 11, 12, 13, 14], $this->lineNumbers($html));
    }

    public function testTheTargetLineIsHighlighted(): void
    {
        $html = $this->dumpLine(self::SAMPLE, 12, 2);

        $this->assertSame(1, substr_count($html, '<mark class="__highlighted-line">'));
        $this->assertMatchesRegularExpression('/<mark class="__highlighted-line"><span class="__line">\s*12 <\/span>/', $html);
    }

    public function testContextIsClampedToTheBeginningOfTheFile(): void
    {
        $this->assertSame([1, 2, 3, 4], $this->lineNumbers($this->dumpLine(self::SAMPLE, 2, 2)));
    }

    public function testContextIsClampedToTheEndOfTheFile(): void
    {
        $lines = $this->lineNumbers($this->dumpLine(self::SAMPLE, 83, 5));

        $this->assertSame(84, max($lines));
        $this->assertSame(78, min($lines));
    }

    public function testNegativeContextShowsTheWholeFile(): void
    {
        $this->assertSame(range(1, 84), $this->lineNumbers($this->dumpLine(self::SAMPLE, 10, -1)));
    }

    public function testZeroContextShowsOnlyTheTargetLine(): void
    {
        $this->assertSame([10], $this->lineNumbers($this->dumpLine(self::SAMPLE, 10, 0)));
    }

    public function testLineNumbersAreAlignedWhenCrossingPowersOfTen(): void
    {
        $html = $this->dumpLine(self::SAMPLE, 9, 1);

        preg_match_all('/<span class="__line">(\s*\d+ )<\/span>/', $html, $matches);

        $widths = array_values(array_unique(array_map('strlen', $matches[1])));
        $this->assertCount(1, $widths, 'All line labels must have the same width: ' . json_encode($matches[1]));
    }

    public function testTargetLinesBeyondTheEndOfTheFileDoNotBreakTheDump(): void
    {
        $html = $this->dumpLine(self::SAMPLE, 500, 2);

        $this->assertStringContainsString('<pre class="__formwork-code">', $html);
    }

    public function testSourceIsHighlightedByTokenType(): void
    {
        $html = $this->dumpLine(self::SAMPLE, 8, 1);

        $this->assertStringContainsString('<span class="__type-keyword">final', $html);
        $this->assertStringContainsString('<span class="__type-name">Sample', $html);
    }

    public function testSourceIsEscaped(): void
    {
        $html = $this->dumpLine(self::HOSTILE, 4, -1);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testStylesAreDumpedOnlyOnce(): void
    {
        ob_start();
        CodeDumper::dumpLine(self::SAMPLE, 5, 1);
        CodeDumper::dumpLine(self::SAMPLE, 6, 1);
        $html = (string) ob_get_clean();

        $this->assertSame(1, substr_count($html, '<style>'));
    }

    public function testMissingFilesAreReported(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->dumpLine(__DIR__ . '/Fixtures/code/missing.php', 1, 1);
    }

    // Backtrace frames

    public function testFramesWithoutFileOrLineAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CodeDumper::dumpBacktraceFrame(['function' => 'foo']);
    }

    public function testFrameWithoutFunctionOnlyDumpsTheCode(): void
    {
        $html = $this->dumpFrame(['file' => self::SAMPLE, 'line' => 10]);

        $this->assertStringContainsString('__highlighted-line', $html);
        $this->assertStringNotContainsString('<pre class="__formwork-trace-call">', $html);
    }

    public function testCalledMethodAndArgumentsAreShown(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'class'    => CodeDumperTarget::class,
            'function' => 'describe',
            'type'     => '::',
            'args'     => ['Ada', 7, 'extra-one', 'extra-two'],
        ]);

        $this->assertStringContainsString('<span class="__name">' . CodeDumperTarget::class . '</span>::<span class="__name">describe</span>()', $html);
        $this->assertStringContainsString('<code>$name</code>', $html);
        $this->assertStringContainsString('"Ada"', $html);
        $this->assertStringContainsString('<code>$count</code>', $html);
        $this->assertStringContainsString('<code>...$rest</code>', $html);
        $this->assertStringContainsString('"extra-one"', $html);
        $this->assertStringContainsString('"extra-two"', $html);
        $this->assertStringContainsString('rowspan="2"', $html, 'The variadic parameter spans one row per value');
    }

    public function testDefaultValuesAreMarked(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'class'    => CodeDumperTarget::class,
            'function' => 'describe',
            'type'     => '::',
            'args'     => ['Ada'],
        ]);

        $this->assertStringContainsString('__type-default">default</span>', $html);
        $this->assertStringContainsString('<span class="__type-number">3</span>', $html);
    }

    public function testSensitiveParametersAreNotRevealed(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'class'    => CodeDumperTarget::class,
            'function' => 'method',
            'type'     => '->',
            'args'     => ['hunter2-super-secret'],
        ]);

        $this->assertStringNotContainsString('hunter2-super-secret', $html);
    }

    public function testExtraArgumentsAreNumberedAfterTheDeclaredOnes(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'function' => 'Formwork\Tests\Unit\Debug\Fixtures\code_dumper_function',
            'args'     => ['one', 'two', 'three', 'four'],
        ]);

        $this->assertStringContainsString('<code>#2</code>', $html);
        $this->assertStringContainsString('<code>#3</code>', $html);
        $this->assertStringNotContainsString('<code>#4</code>', $html);
    }

    public function testUnreflectableFunctionsAreSkipped(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'function' => '{closure}',
            'args'     => ['x'],
        ]);

        $this->assertStringContainsString('<span class="__name">{closure}</span>()', $html);
        $this->assertStringContainsString('"x"', $html);
    }

    public function testFunctionAndClassNamesAreEscaped(): void
    {
        $html = $this->dumpFrame([
            'file'     => self::SAMPLE,
            'line'     => 10,
            'class'    => 'Evil<script>alert(1)</script>',
            'function' => 'run<img src=x onerror=alert(1)>',
            'type'     => '->',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testFramesOfEvaluatedCodeDoNotBreakTheDump(): void
    {
        $html = $this->dumpFrame([
            'file'     => __FILE__ . "(10) : eval()'d code",
            'line'     => 1,
            'function' => 'evaluated',
        ]);

        $this->assertStringContainsString('evaluated', $html);
    }

    /**
     * @return list<int>
     */
    private function lineNumbers(string $html): array
    {
        preg_match_all('/<span class="__line">\s*(\d+) <\/span>/', $html, $matches);

        return array_map('intval', $matches[1]);
    }

    private function dumpLine(string $file, int $line, int $context): string
    {
        ob_start();
        try {
            CodeDumper::dumpLine($file, $line, $context);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @param array<string, mixed> $frame
     */
    private function dumpFrame(array $frame): string
    {
        ob_start();
        try {
            CodeDumper::dumpBacktraceFrame($frame);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
