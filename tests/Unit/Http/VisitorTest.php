<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
use Formwork\Http\Utils\DeviceType;
use Formwork\Http\Utils\Visitor;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Visitor::class)]
final class VisitorTest extends TestCase
{
    #[DataProvider('userAgentProvider')]
    public function testVisitorIsClassifiedByUserAgent(?string $userAgent, bool $bot, DeviceType $device): void
    {
        $request = $this->request($userAgent);

        $this->assertSame($bot, Visitor::isBot($request));
        $this->assertSame(!$bot, Visitor::isBrowser($request));
        $this->assertSame($device, Visitor::getDeviceType($request));
        $this->assertSame($device === DeviceType::Mobile, Visitor::isMobile($request));
        $this->assertSame($device === DeviceType::Tablet, Visitor::isTablet($request));
        $this->assertSame($device === DeviceType::Desktop, Visitor::isDesktop($request));
    }

    /**
     * @return iterable<string, array{?string, bool, DeviceType}>
     */
    public static function userAgentProvider(): iterable
    {
        yield 'desktop browser' => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36', false, DeviceType::Desktop];
        yield 'iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', false, DeviceType::Mobile];
        yield 'Android phone' => ['Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36', false, DeviceType::Mobile];
        yield 'iPad' => ['Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', false, DeviceType::Tablet];
        yield 'Android tablet' => ['Mozilla/5.0 (Linux; Android 13; SM-T870) AppleWebKit/537.36 Chrome/120.0 Safari/537.36', false, DeviceType::Tablet];
        yield 'search engine crawler' => ['Googlebot/2.1', true, DeviceType::Desktop];
        yield 'crawler with a browser like user agent' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)', true, DeviceType::Desktop];
        yield 'command line client' => ['curl/8.0.1', true, DeviceType::Desktop];
        yield 'empty user agent' => ['', false, DeviceType::Desktop];
        yield 'missing user agent' => [null, false, DeviceType::Desktop];
    }

    public function testDeviceTypeDoesNotDependOnThePreviousRequest(): void
    {
        $phone = $this->request('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148');
        $desktop = $this->request('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36');

        $this->assertSame(DeviceType::Mobile, Visitor::getDeviceType($phone));
        $this->assertSame(DeviceType::Desktop, Visitor::getDeviceType($desktop));
        $this->assertSame(DeviceType::Mobile, Visitor::getDeviceType($phone));
    }

    #[DataProvider('refererProvider')]
    public function testSourceIsTheHostOfAnExternalReferer(?string $referer, ?string $expected): void
    {
        $this->assertSame($expected, Visitor::getSource($this->request('Browser/1.0', $referer)));
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function refererProvider(): iterable
    {
        yield 'direct visit' => [null, ''];
        yield 'empty referer' => ['', ''];
        yield 'external site' => ['https://search.example/path?q=1', 'search.example'];
        yield 'external site with a port' => ['https://search.example:8443/path', 'search.example'];
        yield 'external subdomain' => ['https://blog.search.example/path', 'blog.search.example'];
        yield 'subdomain of the current host' => ['https://sub.example.test/path', 'sub.example.test'];
        yield 'external IP address' => ['https://192.0.2.1/path', '192.0.2.1'];
        yield 'current host' => ['https://example.test/previous', null];
        yield 'current host with another case' => ['https://EXAMPLE.test/previous', null];
        yield 'current host with another port' => ['https://example.test:8080/previous', null];
        yield 'relative referer' => ['/previous', null];
        yield 'not a URI' => ['not a uri', null];
        yield 'script URI' => ['javascript:alert(1)', null];
    }

    public function testSourceIsUnknownWhenTheHostOfTheRequestIsInvalid(): void
    {
        $request = new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME'    => 'not a valid host',
            'SERVER_PORT'    => '80',
            'HTTP_REFERER'   => 'https://search.example/path',
        ]);

        $this->assertNull(Visitor::getSource($request));
    }

    private function request(?string $userAgent, ?string $referer = null): Request
    {
        return new Request([], [], [], [], array_filter([
            'REQUEST_METHOD'  => 'GET',
            'SERVER_NAME'     => 'example.test',
            'SERVER_PORT'     => '80',
            'HTTP_USER_AGENT' => $userAgent,
            'HTTP_REFERER'    => $referer,
        ], static fn(?string $value): bool => $value !== null));
    }
}
