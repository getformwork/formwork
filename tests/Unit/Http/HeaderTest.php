<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Header;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Header::class)]
final class HeaderTest extends TestCase
{
    public function testHeaderParsingAndFormatting(): void
    {
        $tokens = Header::split('for=1;proto=https', ';=');

        $this->assertSame([['for', '1'], ['proto', 'https']], $tokens);
        $this->assertSame(['for' => '1', 'proto' => 'https'], Header::combine($tokens));
        $this->assertSame(['application/json' => 1.0, 'text/html' => 0.8], Header::parseQualityValues('text/html;q=.8, application/json'));
        $this->assertSame('X-Custom-Header', Header::fixHeaderName('x_custom_header'));
        $this->assertSame(['X-Test' => 'value'], Header::fixHeaderNames(['x_test' => 'value']));

        $this->expectException(UnexpectedValueException::class);
        Header::combine([[]]);
    }

    public function testHeaderNamesAreNormalized(): void
    {
        $this->assertSame('Content-Type', Header::fixHeaderName('CONTENT_TYPE'));
        $this->assertSame('X-Custom-Header', Header::fixHeaderName('x-custom_header'));
        $this->assertSame('Accept', Header::fixHeaderName('accept'));
    }

    public function testCombineTurnsTokensWithoutValueIntoFlags(): void
    {
        $this->assertSame(
            ['secure' => true, 'max-age' => '5'],
            Header::combine(Header::split('secure; max-age=5', ';=')),
        );
    }

    public function testQualityValuesDefaultToOneAndAreSortedByQuality(): void
    {
        $this->assertSame(
            ['text/html' => 1.0, 'application/json' => 0.9, '*/*' => 0.1],
            Header::parseQualityValues('*/*;q=0.1, application/json;q=0.9, text/html'),
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('etagListProvider')]
    public function testETagsAreParsedFromAHeader(string $header, array $expected): void
    {
        $this->assertSame($expected, Header::parseETags($header));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function etagListProvider(): iterable
    {
        yield 'empty header' => ['', []];
        yield 'only whitespace' => ['   ', []];
        yield 'single ETag' => ['"abc"', ['"abc"']];
        yield 'weak ETag' => ['W/"abc"', ['W/"abc"']];
        yield 'wildcard' => ['*', ['*']];
        yield 'list' => ['"a", "b", "c"', ['"a"', '"b"', '"c"']];
        yield 'list without spaces' => ['"a","b"', ['"a"', '"b"']];
        yield 'list with extra spaces' => ['"a"  ,   "b"', ['"a"', '"b"']];
        yield 'mixed weak and strong ETags' => ['W/"a", "b"', ['W/"a"', '"b"']];
        yield 'empty items' => ['"a",, "b",', ['"a"', '"b"']];
        yield 'ETag containing a comma' => ['"a,b", "c"', ['"a,b"', '"c"']];
        yield 'weak ETag containing a comma' => ['W/"a,b"', ['W/"a,b"']];
    }
}
