<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Utils\Cookie;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Cookie::class)]
final class CookieTest extends TestCase
{
    public function testCookieCanBeSentAndRemovedWithOptions(): void
    {
        $this->assertTrue(Cookie::send('formwork_test', 'value', [
            'path' => '/', 'httpOnly' => true, 'sameSite' => Cookie::SAMESITE_STRICT,
        ]));
        $this->assertTrue(Cookie::remove('formwork_test', ['path' => '/'], forceSend: true));
        $this->assertFalse(Cookie::remove('missing_cookie'));
    }

    public function testInvalidCookieNamesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::send('invalid name', 'value');
    }
}
