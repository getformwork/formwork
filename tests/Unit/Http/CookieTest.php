<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Utils\Cookie;
use Formwork\Tests\PhpServer;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Cookie::class)]
final class CookieTest extends TestCase
{
    private static PhpServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = PhpServer::start(__DIR__ . '/Fixtures/endpoint.php');
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
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

    public function testSendAddsASetCookieHeaderWithTheDefaultAttributes(): void
    {
        $cookies = $this->setCookieHeaders(self::$server->request('action=cookie-defaults')['headers']);

        $this->assertCount(1, $cookies);
        $this->assertStringStartsWith('defaults=value', $cookies[0]);
        $this->assertStringContainsString('SameSite=Lax', $cookies[0]);
        $this->assertStringNotContainsStringIgnoringCase('HttpOnly', $cookies[0]);
        $this->assertStringNotContainsStringIgnoringCase('secure', $cookies[0]);
    }

    public function testSendAppliesTheGivenOptions(): void
    {
        $cookie = $this->setCookieHeaders(self::$server->request('action=cookie-options')['headers'])[0];

        $this->assertStringStartsWith('options=value', $cookie);
        $this->assertStringContainsString('path=/panel', $cookie);
        $this->assertStringContainsString('domain=example.test', $cookie);
        $this->assertMatchesRegularExpression('/; secure(;|$)/i', $cookie);
        $this->assertStringContainsString('HttpOnly', $cookie);
        $this->assertStringContainsString('SameSite=Strict', $cookie);
        $this->assertStringContainsString('expires=Wed, 18 May 2033', $cookie);
    }

    public function testSendingSeveralCookiesKeepsAllOfThem(): void
    {
        $cookies = $this->setCookieHeaders(self::$server->request('action=cookie-multiple')['headers']);

        $this->assertSame(['first=one', 'second=two', 'third=three'], $this->namesAndValues($cookies));
    }

    public function testSendingACookieAgainReplacesItWithoutAffectingTheOthers(): void
    {
        $cookies = $this->setCookieHeaders(self::$server->request('action=cookie-replace')['headers']);

        $this->assertEqualsCanonicalizing(['first=uno', 'second=two'], $this->namesAndValues($cookies));
    }

    public function testRemoveExpiresACookieReceivedWithTheRequest(): void
    {
        $response = self::$server->request('action=cookie-remove-received', ['Cookie' => 'received=value']);
        $cookies = $this->setCookieHeaders($response['headers']);

        $this->assertSame('true', $response['body']);
        $this->assertCount(1, $cookies);
        $this->assertStringStartsWith('received=deleted', $cookies[0]);
        $this->assertStringContainsString('expires=Thu, 01 Jan 1970', $cookies[0]);
        $this->assertStringContainsString('Max-Age=0', $cookies[0]);
    }

    public function testRemoveDoesNothingForUnknownCookies(): void
    {
        $response = self::$server->request('action=cookie-remove-unknown');

        $this->assertSame('false', $response['body']);
        $this->assertSame([], $this->setCookieHeaders($response['headers']));
    }

    public function testRemoveValidatesTheNameToo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::remove('invalid name');
    }

    public function testForcedRemovalExpiresCookiesNotReceivedWithTheRequest(): void
    {
        $response = self::$server->request('action=cookie-remove-forced');
        $cookies = $this->setCookieHeaders($response['headers']);

        $this->assertSame('true', $response['body']);
        $this->assertCount(1, $cookies);
        $this->assertStringStartsWith('unknown=deleted', $cookies[0]);
        $this->assertStringContainsString('expires=Thu, 01 Jan 1970', $cookies[0]);
        $this->assertStringContainsString('path=/panel', $cookies[0]);
    }

    public function testRemovingACookieJustSentCancelsItWithoutAffectingTheOthers(): void
    {
        $response = self::$server->request('action=cookie-remove-pending');

        $this->assertSame('true', $response['body']);
        $this->assertSame(['other=value'], $this->namesAndValues($this->setCookieHeaders($response['headers'])));
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    private function setCookieHeaders(array $headers): array
    {
        return array_values(array_map(
            static fn(string $header): string => substr($header, strlen('Set-Cookie: ')),
            array_filter($headers, static fn(string $header): bool => str_starts_with($header, 'Set-Cookie: ')),
        ));
    }

    /**
     * @param list<string> $cookies
     *
     * @return list<string>
     */
    private function namesAndValues(array $cookies): array
    {
        return array_map(static fn(string $cookie): string => explode(';', $cookie)[0], $cookies);
    }
}
