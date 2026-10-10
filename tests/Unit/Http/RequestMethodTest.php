<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\RequestMethod;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ValueError;

#[CoversClass(RequestMethod::class)]
final class RequestMethodTest extends TestCase
{
    public function testEveryCaseIsBackedByItsUppercaseName(): void
    {
        foreach (RequestMethod::cases() as $method) {
            $this->assertSame($method->name, $method->value);
        }
    }

    public function testSupportedMethods(): void
    {
        $this->assertSame(
            ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            array_map(static fn(RequestMethod $method): string => $method->value, RequestMethod::cases()),
        );
    }

    #[DataProvider('methodNameProvider')]
    public function testMethodsAreCreatedFromTheirName(string $name, RequestMethod $expected): void
    {
        $this->assertSame($expected, RequestMethod::from($name));
        $this->assertSame($expected, RequestMethod::tryFrom($name));
    }

    /**
     * @return iterable<string, array{string, RequestMethod}>
     */
    public static function methodNameProvider(): iterable
    {
        foreach (RequestMethod::cases() as $method) {
            yield $method->value => [$method->value, $method];
        }
    }

    #[DataProvider('invalidMethodNameProvider')]
    public function testUnknownMethodsAreRejected(string $name): void
    {
        $this->assertNull(RequestMethod::tryFrom($name));

        $this->expectException(ValueError::class);
        RequestMethod::from($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidMethodNameProvider(): iterable
    {
        yield 'lowercase' => ['get'];
        yield 'mixed case' => ['Get'];
        yield 'unsupported method' => ['OPTIONS'];
        yield 'unsupported method TRACE' => ['TRACE'];
        yield 'with whitespace' => [' GET'];
        yield 'empty' => [''];
        yield 'arbitrary text' => ['FETCH'];
    }

    #[DataProvider('semanticsProvider')]
    public function testMethodSemantics(RequestMethod $method, bool $safe, bool $idempotent, bool $cacheable): void
    {
        $this->assertSame($safe, $method->isSafe(), "{$method->value} safe");
        $this->assertSame($idempotent, $method->isIdempotent(), "{$method->value} idempotent");
        $this->assertSame($cacheable, $method->isCacheable(), "{$method->value} cacheable");
    }

    /**
     * @return iterable<string, array{RequestMethod, bool, bool, bool}>
     */
    public static function semanticsProvider(): iterable
    {
        yield 'GET' => [RequestMethod::GET, true, true, true];
        yield 'HEAD' => [RequestMethod::HEAD, true, true, true];
        yield 'POST' => [RequestMethod::POST, false, false, false];
        yield 'PUT' => [RequestMethod::PUT, false, true, false];
        yield 'PATCH' => [RequestMethod::PATCH, false, false, false];
        yield 'DELETE' => [RequestMethod::DELETE, false, true, false];
    }

    public function testSafeMethodsAreAlwaysIdempotent(): void
    {
        foreach (RequestMethod::cases() as $method) {
            if ($method->isSafe()) {
                $this->assertTrue($method->isIdempotent(), $method->value);
            }
        }
    }
}
