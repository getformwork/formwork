<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    public function testScheme(): void
    {
        $this->assertSame('http', Uri::scheme('http://example.com'));
        $this->assertSame('https', Uri::scheme('https://example.com'));
        $this->assertSame('ftp', Uri::scheme('ftp://example.com'));
        $this->assertNull(Uri::scheme('example.com'));
    }

    public function testHost(): void
    {
        $this->assertSame('example.com', Uri::host('http://example.com'));
        $this->assertSame('example.com', Uri::host('https://example.com/path'));
        $this->assertSame('example.com', Uri::host('ftp://example.com/resource'));
        $this->assertNull(Uri::host('no-scheme-host'));
    }

    public function testPort(): void
    {
        $this->assertNull(Uri::port('http://example.com'));
        $this->assertNull(Uri::port('https://example.com'));
        $this->assertNull(Uri::port('ftp://example.com'));
        $this->assertSame(8080, Uri::port('http://example.com:8080'));
        $this->assertSame(21, Uri::port('ftp://example.com:21'));
    }

    public function testDefaultPort(): void
    {
        $this->assertSame(80, Uri::getDefaultPort('http'));
        $this->assertSame(443, Uri::getDefaultPort('https'));
    }

    public function testDefaultPortThrowsOnUnknownScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown scheme "sch"');
        Uri::getDefaultPort('sch');
    }

    public function testIsDefaultPort(): void
    {
        $this->assertTrue(Uri::isDefaultPort(80, 'http'));
        $this->assertTrue(Uri::isDefaultPort(443, 'https'));
        $this->assertFalse(Uri::isDefaultPort(8080, 'http'));
        $this->assertFalse(Uri::isDefaultPort(80, 'https'));
    }

    public function testIsDefaultPortThrowsOnUnknownScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown scheme "sch"');
        Uri::isDefaultPort(80, 'sch');
    }

    public function testPath(): void
    {
        $this->assertSame('/path/to/resource', Uri::path('http://example.com/path/to/resource'));
        $this->assertSame('/another/path', Uri::path('https://example.com/another/path?query=string'));
        $this->assertSame('/', Uri::path('ftp://example.com/'));
        $this->assertSame('/path/../test/', Uri::path('/path/../test/?query=string'));
    }

    public function testAbsolutePath(): void
    {
        $this->assertSame('http://example.com/path/to/resource', Uri::absolutePath('http://example.com/path/to/resource'));
        $this->assertSame('https://example.com/another/path', Uri::absolutePath('https://example.com/another/path?query=string'));
        $this->assertSame('ftp://example.com/', Uri::absolutePath('ftp://example.com/'));
    }

    public function testQuery(): void
    {
        $this->assertSame('key=value&foo=bar', Uri::query('http://example.com/path?key=value&foo=bar'));
        $this->assertNull(Uri::query('https://example.com/path'));
    }

    public function testFragment(): void
    {
        $this->assertSame('section1', Uri::fragment('http://example.com/path#section1'));
        $this->assertNull(Uri::fragment('https://example.com/path'));
    }

    public function testQueryToArray(): void
    {
        $this->assertSame(['key' => 'value', 'foo' => 'bar'], Uri::queryToArray('http://example.com/index?key=value&foo=bar'));
        $this->assertSame([], Uri::queryToArray(''));
    }

    public function testParse(): void
    {
        $expected = [
            'scheme'   => 'https',
            'host'     => 'example.com',
            'port'     => 443,
            'path'     => '/path/to/resource',
            'query'    => 'key=value&foo=bar',
            'fragment' => 'section1',
        ];

        $this->assertSame($expected, Uri::parse('https://example.com:443/path/to/resource?key=value&foo=bar#section1'));
    }

    public function testParseThrowsOnMalformedUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI "/;c:354/"');
        Uri::parse('/;c:354/');
    }

    public function testMake(): void
    {
        $this->assertSame('http://localhost:8080/?q=test#top', Uri::make(['port' => 8080, 'query' => 'q=test', 'fragment' => 'top'], 'http://localhost'));
        $this->assertSame('http://localhost:8080/path/?foo=bar&baz=qux#frag', Uri::make(['port' => 8080, 'path' => '/path', 'query' => ['foo' => 'bar', 'baz' => 'qux'], 'fragment' => 'frag'], 'http://localhost'));
        $this->assertSame('http://localhost:80/', Uri::make(['port' => 80], 'http://localhost', true));
        $this->assertSame('https://example.com:443/?x=1', Uri::make(['scheme' => 'https', 'host' => 'example.com', 'port' => 443, 'path' => '/', 'query' => ['x' => '1']], 'http://localhost', true));
        $this->assertSame('https://example.com/search/?q=test+value&lang=en', Uri::make(['scheme' => 'https', 'host' => 'example.com', 'path' => '/search', 'query' => ['q' => 'test value', 'lang' => 'en']], 'http://localhost'));
    }

    public function testNormalize(): void
    {
        $this->assertSame('http://example.com/path/to/resource.jpg', Uri::normalize('http://example.com/path/to/resource.jpg'));
        $this->assertSame('https://example.com/another/path/', Uri::normalize('https://example.com/another/path'));
        $this->assertSame('http://example.com/test/?query=string', Uri::normalize('http://example.com/path/../test/?query=string'));
    }

    public function testRemoveQuery(): void
    {
        $this->assertSame('http://example.com/path/to/resource.jpg', Uri::removeQuery('http://example.com/path/to/resource.jpg?key=value&foo=bar'));
        $this->assertSame('https://example.com/another/path/', Uri::removeQuery('https://example.com/another/path?query=string'));
    }

    public function testRemoveFragment(): void
    {
        $this->assertSame('http://example.com/path/to/resource.jpg', Uri::removeFragment('http://example.com/path/to/resource.jpg#section1'));
        $this->assertSame('https://example.com/another/path/', Uri::removeFragment('https://example.com/another/path#top'));
    }

    public function testResolve(): void
    {
        $this->assertSame('http://example.com/path/to/resource.jpg', Uri::resolveRelative('resource.jpg', 'http://example.com/path/to/'));
        $this->assertSame('https://example.com/path/index.html', Uri::resolveRelative('path/index.html', 'https://example.com/another'));
        $this->assertSame('http://example.com/assets/css/style.css', Uri::resolveRelative('assets/css/style.css', 'http://example.com/'));
    }

    public function testResolveWithFragmentOnly(): void
    {
        $this->assertSame('http://example.com/path/to/resource.jpg#section1', Uri::resolveRelative('#section1', 'http://example.com/path/to/resource.jpg'));
        $this->assertSame('https://example.com/another/path/index.html#top', Uri::resolveRelative('#top', 'https://example.com/another/path/index.html'));
    }

    public function testEncode(): void
    {
        $this->assertSame('/path/to/resource?key=%7B%7D&foo=%C3%89%C6%92#%C3%A5n%C2%A9', Uri::encode('/path/to/resource?key={}&foo=Éƒ#ån©'));
    }
}
