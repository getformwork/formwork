<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
use Formwork\Http\Utils\DeviceType;
use Formwork\Http\Utils\Visitor;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Visitor::class)]
final class VisitorTest extends TestCase
{
    public function testVisitorIdentifiesBotsDevicesAndExternalSources(): void
    {
        $bot = $this->request('Googlebot/2.1', 'https://search.example/path');
        $mobile = $this->request('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148');
        $desktop = $this->request('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36');

        $this->assertTrue(Visitor::isBot($bot));
        $this->assertFalse(Visitor::isBrowser($bot));
        $this->assertSame('search.example', Visitor::getSource($bot));
        $this->assertSame(DeviceType::Mobile, Visitor::getDeviceType($mobile));
        $this->assertTrue(Visitor::isMobile($mobile));
        $this->assertSame(DeviceType::Desktop, Visitor::getDeviceType($desktop));
        $this->assertTrue(Visitor::isDesktop($desktop));
    }

    public function testVisitorHandlesDirectAndSameOriginVisits(): void
    {
        $this->assertSame('', Visitor::getSource($this->request('Browser/1.0')));
        $this->assertNull(Visitor::getSource($this->request('Browser/1.0', 'https://example.test/previous')));
    }

    private function request(string $agent, ?string $referer = null): Request
    {
        return new Request([], [], [], [], array_filter([
            'REQUEST_METHOD'  => 'GET', 'SERVER_NAME' => 'example.test', 'SERVER_PORT' => '80',
            'HTTP_USER_AGENT' => $agent, 'HTTP_REFERER' => $referer,
        ], static fn($value): bool => $value !== null));
    }
}
