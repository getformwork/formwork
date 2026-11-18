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
    public function testBeforeReturnsTheTextBeforeTheFirstOccurrence(): void
    {
        $this->assertSame('Hello', Str::before('Hello, world!', ','));
        $this->assertSame('Hello, world!', Str::before('Hello, world!', 'planet'));
        $this->assertSame('Hello, world!', Str::before('Hello, world!', ''));
    }

    public function testBeforeLastReturnsTheTextBeforeTheLastOccurrence(): void
    {
        $this->assertSame('Hello, world', Str::beforeLast('Hello, world, again!', ','));
        $this->assertSame('Hello, world, again!', Str::beforeLast('Hello, world, again!', 'planet'));
        $this->assertSame('Hello, world, again!', Str::beforeLast('Hello, world, again!', ''));
    }

    public function testAfterReturnsTheTextAfterTheFirstOccurrence(): void
    {
        $this->assertSame(' world!', Str::after('Hello, world!', ','));
        $this->assertSame('Hello, world!', Str::after('Hello, world!', 'planet'));
        $this->assertSame('Hello, world!', Str::after('Hello, world!', ''));
    }

    public function testAfterLastReturnsTheTextAfterTheLastOccurrence(): void
    {
        $this->assertSame(' again!', Str::afterLast('Hello, world, again!', ','));
        $this->assertSame('Hello, world, again!', Str::afterLast('Hello, world, again!', 'planet'));
        $this->assertSame('Hello, world, again!', Str::afterLast('Hello, world, again!', ''));
    }

    public function testEscapeEncodesHtmlSpecialCharacters(): void
    {
        $this->assertSame('Hello &amp; welcome to &lt;Formwork&gt;!', Str::escape('Hello & welcome to <Formwork>!'));
    }

    public function testEscapeAttrEncodesAmpersandsAndQuotes(): void
    {
        $this->assertSame('Hello &amp; welcome to <Formwork> &quot;Framework&quot;!', Str::escapeAttr('Hello & welcome to <Formwork> "Framework"!'));
    }

    public function testEscapeDoesNotEncodeEntitiesTwice(): void
    {
        $this->assertSame('a &amp; b &lt;i&gt; &quot;q&quot; &#039;s&#039;', Str::escape('a &amp; b <i> "q" \'s\''));
    }

    public function testRemoveHtmlStripsTheTags(): void
    {
        $this->assertSame('Hello, welcome to Formwork!', Str::removeHtml('Hello, <b>welcome</b> to Formwork!'));
    }

    public function testSlugsAreLowercaseAndTransliterated(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World!'));
        $this->assertSame('hello-world', Str::slug(' Hello  ~  World !'));
        $this->assertSame('de-aetna-erklaerung', Str::slug('De Ætna Erklärung'));
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
        yield 'uppercase accented letters are transliterated' => ['Ünïcödé Straße', 'uenicoede-strasse'];
        yield 'uppercase accented initial' => ['Über uns', 'ueber-uns'];
        yield 'accented capital at the start of a sentence' => ['È tutto', 'e-tutto'];
        yield 'repeated separators are collapsed' => ['Hello  -  World', 'hello-world'];
        yield 'leading and trailing spaces' => ['  Hello World  ', 'hello-world'];
        yield 'leading and trailing hyphens' => ['--hello--', 'hello'];
        yield 'trailing symbol after a space' => ['Hello World ~', 'hello-world'];
        yield 'leading symbol before a space' => ['~ Hello', 'hello'];
        yield 'newlines become hyphens' => ["Hello\nWorld", 'hello-world'];
        yield 'tabs become hyphens' => ["Hello\tWorld", 'hello-world'];
        yield 'Windows newlines become a single hyphen' => ["Line\r\nBreak", 'line-break'];
        yield 'non-breaking spaces become hyphens' => ["Hello\u{00A0}World", 'hello-world'];
        yield 'backslashes followed by letters' => ['C:\new\temp', 'c-new-temp'];
        yield 'characters not allowed at the start' => ['世界 hello', 'hello'];
        yield 'characters not allowed at the end' => ['Hello 世界', 'hello'];
        yield 'characters not allowed between hyphens' => ['a - 世 - b', 'a-b'];
        yield 'Polish letters' => ['Łódź Kraków', 'lodz-krakow'];
        yield 'Croatian letters' => ['Đorđe Čačak', 'dorde-cacak'];
        yield 'Turkish letters' => ['Şişli Iğdır', 'sisli-igdir'];
        yield 'Romanian letters' => ['Târgu Mureș Brașov', 'targu-mures-brasov'];
        yield 'Czech letters' => ['Dvořák Kůň', 'dvorak-kun'];
        yield 'Hungarian letters' => ['Győr Szőlő Űr', 'gyor-szolo-ur'];
        yield 'Latvian letters' => ['Rīga Ķemeri', 'riga-kemeri'];
        yield 'typographic apostrophes become hyphens' => ['L’amore', 'l-amore'];
        yield 'quotes become hyphens' => ['"Hello" “world”', 'hello-world'];
        yield 'Unicode whitespace becomes hyphens' => ["Em\u{2003}space\u{3000}here", 'em-space-here'];
        // Like `\s` in JavaScript, which makes the slugs of the Panel
        yield 'zero width no-break spaces become hyphens' => ["Zero\u{FEFF}width", 'zero-width'];
        yield 'next line characters are removed' => ["Next\u{0085}line", 'nextline'];
        yield 'only symbols' => ['!!!', ''];
        yield 'empty' => ['', ''];
    }

    public function testAppendAddsASuffix(): void
    {
        $this->assertSame('Hello, world!!!', Str::append('Hello, world', '!!!'));
    }

    public function testPrependAddsAPrefix(): void
    {
        $this->assertSame('!!!Hello, world', Str::prepend('Hello, world', '!!!'));
    }

    public function testWrapAddsTheSameTextOnBothSides(): void
    {
        $this->assertSame('**Hello, world**', Str::wrap('Hello, world', '**'));
    }

    public function testRemoveStartRemovesOnlyAMatchingPrefix(): void
    {
        $this->assertSame('world!', Str::removeStart('Hello, world!', 'Hello, '));
        $this->assertSame('Hello, world!', Str::removeStart('Hello, world!', 'world'));
    }

    public function testRemoveEndRemovesOnlyAMatchingSuffix(): void
    {
        $this->assertSame('Hello, world', Str::removeEnd('Hello, world!', '!'));
        $this->assertSame('Hello, world!', Str::removeEnd('Hello, world!', 'Hello'));
    }

    public function testDotNotationIsConvertedToBrackets(): void
    {
        $this->assertSame('array[key1][key2]', Str::dotNotationToBrackets('array.key1.key2'));
        $this->assertSame('array', Str::dotNotationToBrackets('array'));
    }

    public function testInterpolateReplacesThePlaceholders(): void
    {
        $this->assertSame('Hello, Test User! Welcome to Formwork.', Str::interpolate('Hello, {{name}}! Welcome to {{platform}}.', ['name' => 'Test User', 'platform' => 'Formwork']));
    }

    public function testInterpolatePreservesEscapedSequencesEvenIfTheKeyIsDefined(): void
    {
        $this->assertSame('Hello, {{name}}!', Str::interpolate('Hello, \{{name}}!', ['name' => 'Test User']));
    }

    public function testInterpolateLeavesUndefinedKeysUntouched(): void
    {
        $this->assertSame('Hello, Test User! Welcome to {{platform}}.', Str::interpolate('Hello, {{name}}! Welcome to {{platform}}.', ['name' => 'Test User']));
    }

    public function testInterpolateCanResolveThePlaceholdersWithACallback(): void
    {
        $this->assertSame('Hello, Test User! Welcome to platform.', Str::interpolate('Hello, {{name}}! Welcome to {{platform}}.', function ($key) {
            return match ($key) {
                'name'  => 'Test User',
                default => $key,
            };
        }));
    }

    public function testChunkSplitsTheTextEveryGivenLength(): void
    {
        $this->assertSame('Hel lo,  wo rld !', Str::chunk('Hello, world!', 3, ' '));
        $this->assertSame('Hello, world!', Str::chunk('Hello, world!', 20, ' '));
        $this->assertSame('a-b-c', Str::chunk('abc', 1, '-'));
    }

    #[DataProvider('nonPositiveLengthProvider')]
    public function testChunkRejectsNonPositiveLengths(int $length): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('$length must be greater than 0');

        Str::chunk('Hello, world!', $length, ' ');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonPositiveLengthProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    public function testRemoveHTMLStripsTagsAndDecodesEntities(): void
    {
        $this->assertSame('"User\'s" <b> & co', Str::removeHTML('<p>&quot;User&apos;s&quot; &lt;b&gt; <em>&amp;</em> co</p>'));
    }

    public function testCamelCaseJoinsSnakeAndDashCaseWords(): void
    {
        $this->assertSame('helloWorldString', Str::toCamelCase('hello_world_string'));
        $this->assertSame('helloWorldString', Str::toCamelCase('hello-world-string'));
    }

    #[DataProvider('caseConversionProvider')]
    public function testCaseConversionsAreConsistent(string $input, string $dash, string $snake, string $camel): void
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
