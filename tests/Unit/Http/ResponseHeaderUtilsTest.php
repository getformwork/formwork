<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseStatus;
use Formwork\Http\Utils\Header;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Header::class)]
final class ResponseHeaderUtilsTest extends TestCase
{
    public function testRedirectRejectsNonRedirectionStatuses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only 3XX statuses are allowed');
        Header::redirect('/next', ResponseStatus::OK);
    }

    /**
     * @param list<int|string> $data
     */
    #[DataProvider('makeProvider')]
    public function testMakeJoinsValuesAndParameters(array $data, string $expected): void
    {
        $this->assertSame($expected, Header::make($this->unchecked($data)));
    }

    /**
     * @return iterable<string, array{array<int|string, string>, string}>
     */
    public static function makeProvider(): iterable
    {
        yield 'no data' => [[], ''];
        yield 'single value' => [['text/html'], 'text/html'];
        yield 'value with a parameter' => [['application/json', 'charset' => 'utf-8'], 'application/json; charset=utf-8'];
        yield 'several parameters' => [['attachment', 'filename' => 'sample.txt', 'size' => '12'], 'attachment; filename=sample.txt; size=12'];
        yield 'parameters before values' => [['a' => 'b', 'c'], 'a=b; c'];
    }
}
