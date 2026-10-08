<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Str::class)]
final class StrTest extends TestCase
{
    public function testBefore(): void
    {
        $this->assertSame('Hello', Str::before('Hello, world!', ','));
        $this->assertSame('Hello, world!', Str::before('Hello, world!', 'planet'));
        $this->assertSame('Hello, world!', Str::before('Hello, world!', ''));
    }

    public function testBeforeLast(): void
    {
        $this->assertSame('Hello, world', Str::beforeLast('Hello, world, again!', ','));
        $this->assertSame('Hello, world, again!', Str::beforeLast('Hello, world, again!', 'planet'));
        $this->assertSame('Hello, world, again!', Str::beforeLast('Hello, world, again!', ''));
    }

    public function testAfter(): void
    {
        $this->assertSame(' world!', Str::after('Hello, world!', ','));
        $this->assertSame('Hello, world!', Str::after('Hello, world!', 'planet'));
        $this->assertSame('Hello, world!', Str::after('Hello, world!', ''));
    }

    public function testAfterLast(): void
    {
        $this->assertSame(' again!', Str::afterLast('Hello, world, again!', ','));
        $this->assertSame('Hello, world, again!', Str::afterLast('Hello, world, again!', 'planet'));
        $this->assertSame('Hello, world, again!', Str::afterLast('Hello, world, again!', ''));
    }

    public function testEscape(): void
    {
        $this->assertSame('Hello &amp; welcome to &lt;Formwork&gt;!', Str::escape('Hello & welcome to <Formwork>!'));
    }

    public function testEscapeAttr(): void
    {
        $this->assertSame('Hello &amp; welcome to <Formwork> &quot;Framework&quot;!', Str::escapeAttr('Hello & welcome to <Formwork> "Framework"!'));
    }

    public function testRemoveTags(): void
    {
        $this->assertSame('Hello, welcome to Formwork!', Str::removeHtml('Hello, <b>welcome</b> to Formwork!'));
    }

    public function testSlug(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World!'));
        $this->assertSame('hello-world', Str::slug(' Hello  ~  World !'));
        $this->assertSame('de-etna-erklaerung', Str::slug('De Ætna Erklärung'));
    }

    public function testAppend(): void
    {
        $this->assertSame('Hello, world!!!', Str::append('Hello, world', '!!!'));
    }

    public function testPrepend(): void
    {
        $this->assertSame('!!!Hello, world', Str::prepend('Hello, world', '!!!'));
    }

    public function testWrap(): void
    {
        $this->assertSame('**Hello, world**', Str::wrap('Hello, world', '**'));
    }

    public function testRemoveStart(): void
    {
        $this->assertSame('world!', Str::removeStart('Hello, world!', 'Hello, '));
        $this->assertSame('Hello, world!', Str::removeStart('Hello, world!', 'world'));
    }

    public function testRemoveEnd(): void
    {
        $this->assertSame('Hello, world', Str::removeEnd('Hello, world!', '!'));
        $this->assertSame('Hello, world!', Str::removeEnd('Hello, world!', 'Hello'));
    }

    public function testDotToBrackets(): void
    {
        $this->assertSame('array[key1][key2]', Str::dotNotationToBrackets('array.key1.key2'));
        $this->assertSame('array', Str::dotNotationToBrackets('array'));
    }

    public function testInterpolate(): void
    {
        $this->assertSame('Hello, John Doe! Welcome to Formwork.', Str::interpolate('Hello, {{name}}! Welcome to {{platform}}.', ['name' => 'John Doe', 'platform' => 'Formwork']));
        $this->assertSame('Hello, {{name}}! Welcome to {{platform}}.', Str::interpolate('Hello, \{{name}}! Welcome to \{{platform}}.', []));
    }

    public function testInterpolateWithClosure(): void
    {
        $this->assertSame('Hello, John Doe! Welcome to platform.', Str::interpolate('Hello, {{name}}! Welcome to {{platform}}.', function ($key) {
            return match ($key) {
                'name'  => 'John Doe',
                default => $key,
            };
        }));
    }

    public function testChunk(): void
    {
        $this->assertSame('Hel lo,  wo rld !', Str::chunk('Hello, world!', 3, ' '));
        $this->assertSame('Hello, world!', Str::chunk('Hello, world!', 20, ' '));
    }

    public function testChunkThrowsOnNonPositiveLength(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('$length must be greater than 0');
        Str::chunk('Hello, world!', -1, ' ');
    }

    public function testDashCase(): void
    {
        $this->assertSame('hello-world-string', Str::toDashCase('HelloWorldString'));
    }

    public function testSnakeCase(): void
    {
        $this->assertSame('hello_world_string', Str::toSnakeCase('HelloWorldString'));
    }

    public function testCamelCase(): void
    {
        $this->assertSame('helloWorldString', Str::toCamelCase('hello_world_string'));
        $this->assertSame('helloWorldString', Str::toCamelCase('hello-world-string'));
    }

    public function testEscapeDoesNotEncodeEntitiesTwice(): void
    {
        $this->assertSame('a &amp; b &lt;i&gt; &quot;q&quot; &#039;s&#039;', Str::escape('a &amp; b <i> "q" \'s\''));
    }

    #[DataProvider('slugProvider')]
    public function testSlugContainsOnlyLowercaseWordsSeparatedBySingleHyphens(string $input, string $expected): void
    {
        $this->assertSame($expected, Str::slug($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function slugProvider(): iterable
    {
        yield 'sentence' => ['Hello, World!', 'hello-world'];
        yield 'surrounding punctuation' => ['!hello!', 'hello'];
        yield 'angle brackets and equals sign' => ['a<b>c=d', 'abcd'];
        yield 'dots are removed' => ['a.b.c', 'abc'];
        yield 'underscores become hyphens' => ['a_b', 'a-b'];
        yield 'apostrophes become hyphens' => ["it's", 'it-s'];
        yield 'lowercase accented letters are transliterated' => ['crème brûlée', 'creme-brulee'];
        yield 'German sharp s and umlauts' => ['Straße Erklärung', 'strasse-erklaerung'];
        yield 'uppercase accented letters are transliterated' => ['Ünïcödé Straße', 'unicoede-strasse'];
        yield 'uppercase accented initial' => ['Über uns', 'ueber-uns'];
        yield 'accented capital at the start of a sentence' => ['È tutto', 'e-tutto'];
        yield 'repeated separators are collapsed' => ['Hello  -  World', 'hello-world'];
        yield 'leading and trailing spaces' => ['  Hello World  ', 'hello-world'];
        yield 'leading and trailing hyphens' => ['--hello--', 'hello'];
        yield 'trailing symbol after a space' => ['Hello World ~', 'hello-world'];
        yield 'leading symbol before a space' => ['~ Hello', 'hello'];
        yield 'only symbols' => ['!!!', ''];
        yield 'empty' => ['', ''];
    }

    public function testChunkWithZeroLengthIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        Str::chunk('Hello', 0, ' ');
    }

    #[DataProvider('caseConversionProvider')]
    public function testCaseConversions(string $input, string $dash, string $snake, string $camel): void
    {
        $this->assertSame($dash, Str::toDashCase($input));
        $this->assertSame($snake, Str::toSnakeCase($input));
        $this->assertSame($camel, Str::toCamelCase($input));
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function caseConversionProvider(): iterable
    {
        yield 'camel case' => ['camelCase', 'camel-case', 'camel_case', 'camelCase'];
        yield 'pascal case' => ['PascalCase', 'pascal-case', 'pascal_case', 'pascalCase'];
        yield 'acronym followed by a word' => ['XMLHttpRequest', 'xml-http-request', 'xml_http_request', 'xMLHttpRequest'];
        yield 'dash case' => ['already-dash', 'already-dash', 'already-dash', 'alreadyDash'];
        yield 'snake case' => ['snake_case', 'snake_case', 'snake_case', 'snakeCase'];
        yield 'single letter' => ['a', 'a', 'a', 'a'];
    }
}
