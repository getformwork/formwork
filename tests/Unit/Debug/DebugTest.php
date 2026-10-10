<?php

namespace Formwork\Tests\Unit\Debug;

use DOMDocument;
use Formwork\Debug\Debug;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Debug\Fixtures\DumpEnum;
use Formwork\Tests\Unit\Debug\Fixtures\DumpTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use stdClass;

#[CoversClass(Debug::class)]
final class DebugTest extends TestCase
{
    private ?string $originalAccept = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAccept = $_SERVER['HTTP_ACCEPT'] ?? null;
        $this->resetState();
    }

    protected function tearDown(): void
    {
        if ($this->originalAccept === null) {
            unset($_SERVER['HTTP_ACCEPT']);
        } else {
            $_SERVER['HTTP_ACCEPT'] = $this->originalAccept;
        }
        $this->resetState();
        parent::tearDown();
    }

    // Scalars

    public function testBooleans(): void
    {
        $this->assertSame('<span class="__type-bool">true</span>', $this->body(true));
        $this->assertSame('<span class="__type-bool">false</span>', $this->body(false));
    }

    public function testNumbers(): void
    {
        $this->assertSame('<span class="__type-number">42</span>', $this->body(42));
        $this->assertSame('<span class="__type-number">-7</span>', $this->body(-7));
        $this->assertSame('<span class="__type-number">1.5</span>', $this->body(1.5));
        $this->assertSame('<span class="__type-number">INF</span>', $this->body(INF));
        $this->assertSame('<span class="__type-number">NAN</span>', $this->body(NAN));
    }

    public function testNull(): void
    {
        $this->assertSame('<span class="__type-null">null</span>', $this->body(null));
    }

    public function testStrings(): void
    {
        $this->assertSame('<span class="__type-string">"hello"</span>', $this->body('hello'));
        $this->assertSame('<span class="__type-string">""</span>', $this->body(''));
    }

    public function testStringsAreEscaped(): void
    {
        $this->assertSame('<span class="__type-string">"&lt;b&gt;&quot;x&quot; &amp; &#039;y&#039;"</span>', $this->body('<b>"x" & \'y\''));
    }

    public function testBinaryStringsAreFlagged(): void
    {
        $output = $this->body("\xff\xfe");

        $this->assertStringContainsString('>b"', $output);
    }

    public function testMultilineStringsUseTripleQuotes(): void
    {
        $output = $this->body("first\nsecond");

        $this->assertStringContainsString('"""', $output);
        $this->assertStringContainsString("  first\n  second\n", $output);
    }

    public function testCarriageReturnsAreTreatedAsLineBreaks(): void
    {
        $this->assertStringContainsString('"""', $this->body("first\rsecond"));
    }

    // Arrays

    public function testEmptyArray(): void
    {
        $output = $this->body([]);

        $this->assertStringContainsString('array</span>(<span class="__note">0</span>)', $output);
    }

    public function testListsDoNotShowTheirKeys(): void
    {
        $output = $this->body(['a', 'b']);

        $this->assertStringNotContainsString('=&gt;', $output);
        $this->assertStringNotContainsString(' => ', strip_tags($output) === '' ? '' : $output);
        $this->assertStringContainsString('"a"', $output);
        $this->assertStringContainsString('(<span class="__note">2</span>)', $output);
    }

    public function testAssociativeArraysShowTheirKeys(): void
    {
        $output = $this->body(['name' => 'value', 3 => 'x']);

        $this->assertStringContainsString('"name"</span> => ', $output);
        $this->assertStringContainsString('<span class="__type-number">3</span> => ', $output);
    }

    public function testArrayKeysAreEscaped(): void
    {
        $output = $this->body(['<script>alert(1)</script>' => 1]);

        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testReferencesInArraysAreMarked(): void
    {
        $value = 'shared';
        $array = [&$value];

        $this->assertStringContainsString('title="Reference"', $this->body($array));
        $this->assertStringNotContainsString('title="Reference"', $this->body(['shared']));
    }

    public function testNestedArraysAreCollapsibleWithUniqueIdentifiers(): void
    {
        $output = $this->body([[1], [2]]);

        preg_match_all('/id="(__formwork-dump-id-\d+)"/', $output, $matches);
        $this->assertCount(3, $matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testIdentifiersKeepIncreasingBetweenDumps(): void
    {
        preg_match('/id="__formwork-dump-id-(\d+)"/', $this->body([1]), $first);
        preg_match('/id="__formwork-dump-id-(\d+)"/', $this->body([1]), $second);

        $this->assertGreaterThan((int) $first[1], (int) $second[1]);
    }

    #[RunInSeparateProcess]
    public function testSelfReferencingArraysDoNotExhaustTheStack(): void
    {
        ini_set('memory_limit', '256M');
        $array = ['name' => 'loop'];
        $array['self'] = &$array;

        $output = Debug::dumpToString($array);

        $this->assertStringContainsString('loop', $output);
    }

    // Objects

    public function testObjectsShowTheirClassIdAndProperties(): void
    {
        $object = new DumpTarget();
        $output = $this->body($object);

        $this->assertStringContainsString(DumpTarget::class, $output);
        $this->assertStringContainsString('#' . spl_object_id($object), $output);
        $this->assertStringContainsString('"public value"', $output);
        $this->assertStringContainsString('"promoted value"', $output);
        $this->assertStringContainsString('<span class="__type-number">42</span>', $output);
    }

    public function testVisibilityOfPropertiesIsShown(): void
    {
        $output = $this->body(new DumpTarget());

        $this->assertMatchesRegularExpression('/__visibility-public" title="Public property">public<\/span><span class="__type-property">visible</', $output);
        $this->assertMatchesRegularExpression('/__visibility-protected" title="Protected property">protected<\/span><span class="__type-property">guarded</', $output);
        $this->assertMatchesRegularExpression('/__visibility-private" title="Private property">private<\/span><span class="__type-property">hidden</', $output);
    }

    public function testObjectsWithoutPropertiesAreDumpedEmpty(): void
    {
        $output = $this->body(new stdClass());

        $this->assertStringContainsString('stdClass', $output);
        $this->assertStringNotContainsString('__formwork-dump-toggle', $output);
    }

    public function testEnumsAreDumpedByCase(): void
    {
        $this->assertSame('<span class="__type-name">' . DumpEnum::class . '</span>::<span class="__type-name">Second</span>', $this->body(DumpEnum::Second));
    }

    public function testCircularReferencesAreCut(): void
    {
        $a = new DumpTarget();
        $b = new DumpTarget();
        $a->next = $b;
        $b->next = $a;

        $output = $this->body($a);

        $this->assertStringContainsString('Go to reference', $output);
    }

    public function testDumpingTheSameObjectTwiceDoesNotReportAReference(): void
    {
        $object = new DumpTarget();

        $first = $this->body($object);
        $second = $this->body($object);

        $this->assertStringNotContainsString('Go to reference', $second, 'The second dump is not a circular reference');
        $this->assertSame(
            preg_replace('/__formwork-dump-id-\d+/', 'ID', $first),
            preg_replace('/__formwork-dump-id-\d+/', 'ID', $second),
        );
    }

    public function testTheSameObjectUsedTwiceInOneStructureIsFullyDumpedBothTimes(): void
    {
        $shared = new DumpTarget('shared');

        $output = $this->body([$shared, $shared]);

        $this->assertSame(2, substr_count($output, '"shared"'));
    }

    public function testPropertyNamesAreEscaped(): void
    {
        $object = new stdClass();
        $object->{'<img src=x onerror=alert(1)>'} = 'value';

        $output = $this->body($object);

        $this->assertStringNotContainsString('<img', $output);
    }

    public function testClassNamesOfDecodedPayloadsCannotInjectMarkup(): void
    {
        $payload = json_decode('{"<script>alert(1)</script>": {"<b>nested</b>": 1}}');

        $document = new DOMDocument();
        @$document->loadHTML('<body>' . $this->body($payload));

        $this->assertSame(0, $document->getElementsByTagName('script')->length);
        $this->assertSame(0, $document->getElementsByTagName('b')->length);
    }

    public function testClosuresShowTheirSignature(): void
    {
        $closure = static fn(int $count, string ...$names): bool => true;

        $output = $this->body($closure);

        $this->assertStringContainsString('Closure</span>(', $output);
        $this->assertStringContainsString('<span class="__type-keyword">int</span> <span class="__type-property">$count</span>', $output);
        $this->assertStringContainsString('...<span class="__type-property">$names</span>', $output);
        $this->assertStringContainsString('): <span class="__type-keyword">bool</span>', $output);
    }

    public function testClosuresShowTheirDefinitionSite(): void
    {
        $output = $this->body(static fn() => null);

        $this->assertStringContainsString('<span class="__type-property">file</span>', $output);
        $this->assertStringContainsString(basename(__FILE__), $output);
        $this->assertStringContainsString('<span class="__type-property">line</span>', $output);
    }

    public function testStaticClosuresDoNotExposeAThisObject(): void
    {
        $output = $this->body(static fn() => null);

        $this->assertStringNotContainsString('<span class="__type-property">this</span>', $output);
    }

    public function testNonStaticClosuresExposeTheirScope(): void
    {
        $output = $this->body(fn() => $this);

        $this->assertStringContainsString('<span class="__type-property">scope</span>', $output);
        $this->assertStringContainsString(self::class, $output);
    }

    public function testResources(): void
    {
        $handle = fopen('php://memory', 'r');
        $this->assertNotFalse($handle);

        try {
            $output = $this->body($handle);
        } finally {
            fclose($handle);
        }

        $this->assertStringContainsString('resource</span>(<span class="__type-name">stream</span>', $output);
    }

    public function testClosedResourcesAreDumpedInsteadOfBreakingTheDump(): void
    {
        $handle = fopen('php://memory', 'r');
        $this->assertNotFalse($handle);
        fclose($handle);

        $this->assertStringContainsString('resource', $this->body($handle));
    }

    // dumpToString() and dump()

    public function testDumpToStringWrapsTheOutputInAPreElement(): void
    {
        $output = Debug::dumpToString(1);

        $this->assertSame('<pre class="__formwork-dump"><span class="__type-number">1</span></pre>', $output);
    }

    public function testStylesAreEchoedOnceWhenRequested(): void
    {
        ob_start();
        Debug::dumpToString(1, true);
        Debug::dumpToString(2, true);
        $echoed = (string) ob_get_clean();

        $this->assertSame(1, substr_count($echoed, '<style>'));
        $this->assertSame(1, substr_count($echoed, '<script>'));
    }

    public function testStylesAreNotEchoedByDefault(): void
    {
        ob_start();
        Debug::dumpToString(1);
        $echoed = (string) ob_get_clean();

        $this->assertSame('', $echoed);
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function acceptHeaders(): iterable
    {
        yield 'html' => ['text/html', true];
        yield 'browser' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', true];
        yield 'json' => ['application/json', false];
        yield 'wildcard only' => ['*/*', false];
        yield 'missing' => [null, false];
    }

    #[DataProvider('acceptHeaders')]
    public function testDumpOnlyOutputsForClientsAcceptingHtml(?string $accept, bool $expected): void
    {
        if ($accept === null) {
            unset($_SERVER['HTTP_ACCEPT']);
        } else {
            $_SERVER['HTTP_ACCEPT'] = $accept;
        }

        $level = ob_get_level();
        ob_start();
        Debug::dump('value');
        $output = '';
        while (ob_get_level() > $level) {
            $output = (string) ob_get_clean() . $output;
        }

        $this->assertSame($expected, str_contains($output, '<pre class="__formwork-dump">'));
    }

    public function testDumpShowsTheCallerContextAndEveryArgument(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $level = ob_get_level();
        ob_start();
        Debug::dump('first', 2);
        $output = '';
        while (ob_get_level() > $level) {
            $output = (string) ob_get_clean() . $output;
        }

        $this->assertStringContainsString(basename(__FILE__), $output);
        $this->assertSame(3, substr_count($output, '__formwork-dump-item"'), 'One item for the context and one for each argument');
        $this->assertStringContainsString('"first"', $output);
        $this->assertStringContainsString('>2<', $output);
    }

    // Helpers

    /**
     * The dumped value without the surrounding pre element
     */
    private function body(mixed $value): string
    {
        $output = Debug::dumpToString($value);

        return substr($output, strlen('<pre class="__formwork-dump">'), -strlen('</pre>'));
    }

    private function resetState(): void
    {
        (new ReflectionProperty(Debug::class, 'refs'))->setValue(null, []);
        (new ReflectionProperty(Debug::class, 'counter'))->setValue(null, 0);
        (new ReflectionProperty(Debug::class, 'stylesDumped'))->setValue(null, false);
    }
}
