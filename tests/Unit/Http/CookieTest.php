<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Utils\Cookie;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Cookie::class)]
final class CookieTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalCookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCookies = $_COOKIE;
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookies;
        parent::tearDown();
    }

    #[DataProvider('invalidNameProvider')]
    public function testInvalidCookieNamesAreRejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookie name');
        Cookie::send($name, 'value');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNameProvider(): iterable
    {
        yield 'space' => ['invalid name'];
        yield 'empty name' => [''];
        yield 'semicolon' => ['a;b'];
        yield 'equal sign' => ['a=b'];
        yield 'comma' => ['a,b'];
        yield 'slash' => ['a/b'];
        yield 'brackets' => ['a[b]'];
        yield 'control character' => ["a\nb"];
        yield 'non ASCII character' => ['caffè'];
    }

    public function testRemoveDoesNothingForUnknownCookies(): void
    {
        $this->assertFalse(Cookie::remove('unknown'));
    }

    public function testRemoveValidatesTheNameToo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::remove('invalid name');
    }
}
