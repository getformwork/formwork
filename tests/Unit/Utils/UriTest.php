<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    public function testSchemeIsReadFromTheUri(): void
    {
        $this->assertSame('http', Uri::scheme('http://example.test'));
        $this->assertSame('https', Uri::scheme('https://example.test'));
        $this->assertSame('ftp', Uri::scheme('ftp://example.test'));
        $this->assertNull(Uri::scheme('example.test'));
    }

    public function testHostIsReadFromTheUri(): void
    {
        $this->assertSame('example.test', Uri::host('http://example.test'));
        $this->assertSame('example.test', Uri::host('https://example.test/path'));
        $this->assertSame('example.test', Uri::host('ftp://example.test/resource'));
        $this->assertNull(Uri::host('no-scheme-host'));
    }

    public function testPortIsNullUnlessExplicit(): void
    {
        $this->assertNull(Uri::port('http://example.test'));
        $this->assertNull(Uri::port('https://example.test'));
        $this->assertNull(Uri::port('ftp://example.test'));
        $this->assertSame(8080, Uri::port('http://example.test:8080'));
        $this->assertSame(21, Uri::port('ftp://example.test:21'));
    }

    public function testDefaultPortsOfHttpAndHttps(): void
    {
        $this->assertSame(80, Uri::getDefaultPort('http'));
        $this->assertSame(443, Uri::getDefaultPort('https'));
    }

    public function testDefaultPortRejectsUnknownSchemes(): void
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

    public function testIsDefaultPortRejectsUnknownSchemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown scheme "sch"');
        Uri::isDefaultPort(80, 'sch');
    }

    public function testPathIsReadFromTheUri(): void
    {
        $this->assertSame('/path/to/resource', Uri::path('http://example.test/path/to/resource'));
        $this->assertSame('/another/path', Uri::path('https://example.test/another/path?query=string'));
        $this->assertSame('/', Uri::path('ftp://example.test/'));
        $this->assertSame('/path/../test/', Uri::path('/path/../test/?query=string'));
    }

    public function testAbsolutePathRemovesQueryAndFragment(): void
    {
        $this->assertSame('http://example.test/path/to/resource', Uri::absolutePath('http://example.test/path/to/resource'));
        $this->assertSame('https://example.test/another/path', Uri::absolutePath('https://example.test/another/path?query=string'));
        $this->assertSame('ftp://example.test/', Uri::absolutePath('ftp://example.test/'));
    }

    public function testQueryIsReadFromTheUri(): void
    {
        $this->assertSame('key=value&foo=bar', Uri::query('http://example.test/path?key=value&foo=bar'));
        $this->assertNull(Uri::query('https://example.test/path'));
    }

    public function testFragmentIsReadFromTheUri(): void
    {
        $this->assertSame('section1', Uri::fragment('http://example.test/path#section1'));
        $this->assertNull(Uri::fragment('https://example.test/path'));
    }

    public function testQueryToArrayParsesTheQueryString(): void
    {
        $this->assertSame(['key' => 'value', 'foo' => 'bar'], Uri::queryToArray('http://example.test/index?key=value&foo=bar'));
        $this->assertSame([], Uri::queryToArray(''));
    }

    public function testParseReturnsEveryComponent(): void
    {
        $expected = [
            'scheme'   => 'https',
            'host'     => 'example.test',
            'port'     => 443,
            'path'     => '/path/to/resource',
            'query'    => 'key=value&foo=bar',
            'fragment' => 'section1',
        ];

        $this->assertSame($expected, Uri::parse('https://example.test:443/path/to/resource?key=value&foo=bar#section1'));
    }

    public function testParseRejectsMalformedUris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI "/;c:354/"');
        Uri::parse('/;c:354/');
    }

    public function testMakeCombinesTheGivenPartsWithTheBaseUri(): void
    {
        $this->assertSame('http://localhost:8080/?q=test#top', Uri::make(['port' => 8080, 'query' => 'q=test', 'fragment' => 'top'], 'http://localhost'));
        $this->assertSame('http://localhost:8080/path/?foo=bar&baz=qux#frag', Uri::make(['port' => 8080, 'path' => '/path', 'query' => ['foo' => 'bar', 'baz' => 'qux'], 'fragment' => 'frag'], 'http://localhost'));
        $this->assertSame('http://localhost:80/', Uri::make(['port' => 80], 'http://localhost', true));
        $this->assertSame('https://example.test:443/?x=1', Uri::make(['scheme' => 'https', 'host' => 'example.test', 'port' => 443, 'path' => '/', 'query' => ['x' => '1']], 'http://localhost', true));
        $this->assertSame('https://example.test/search/?q=test+value&lang=en', Uri::make(['scheme' => 'https', 'host' => 'example.test', 'path' => '/search', 'query' => ['q' => 'test value', 'lang' => 'en']], 'http://localhost'));
    }

    public function testMakePreservesTheNonDefaultPortOfTheGivenUri(): void
    {
        $this->assertSame('http://localhost:8080/b/', Uri::make(['path' => '/b'], 'http://localhost:8080/a'));
        $this->assertSame('http://localhost:8080/a/', Uri::make([], 'http://localhost:8080/a'));
    }

    public function testMakePreservesExplicitPortsOfOtherSchemes(): void
    {
        $this->assertSame('http://example.test:443/a/', Uri::make(['port' => 443], 'http://example.test/a'));
        $this->assertSame('https://example.test:80/a/', Uri::make(['scheme' => 'https', 'port' => 80], 'http://example.test/a'));
    }

    public function testMakeOmitsDefaultPortsUnlessForced(): void
    {
        $this->assertSame('http://example.test/a/', Uri::make(['port' => 80], 'http://example.test/a'));
        $this->assertSame('https://example.test/a/', Uri::make(['scheme' => 'https', 'port' => 443], 'http://example.test/a'));
        $this->assertSame('http://example.test:80/a/', Uri::make([], 'http://example.test/a', forcePort: true));
        $this->assertSame('https://example.test:443/a/', Uri::make(['scheme' => 'https'], 'http://example.test/a', forcePort: true));
    }

    public function testMakeLowercasesSchemeAndHost(): void
    {
        $this->assertSame('https://example.test/a/', Uri::make(['scheme' => 'HTTPS', 'host' => 'EXAMPLE.test'], '/a'));
    }

    public function testMakeOnlyAddsTrailingSlashesToDirectories(): void
    {
        $this->assertSame('http://example.test/file.txt', Uri::make(['path' => 'file.txt'], 'http://example.test/a'));
        $this->assertSame('http://example.test/dir/', Uri::make(['path' => 'dir'], 'http://example.test/a'));
        $this->assertSame('/', Uri::make(['path' => ''], ''));
    }

    public function testMakeIgnoresEmptyQueriesAndFragments(): void
    {
        $this->assertSame('http://example.test/a/', Uri::make(['query' => '', 'fragment' => ''], 'http://example.test/a'));
        $this->assertSame('http://example.test/a/?x=1#top', Uri::make(['query' => '?x=1', 'fragment' => '#top'], 'http://example.test/a'));
    }

    public function testNormalizeCleansUpTheUri(): void
    {
        $this->assertSame('http://example.test/path/to/resource.jpg', Uri::normalize('http://example.test/path/to/resource.jpg'));
        $this->assertSame('https://example.test/another/path/', Uri::normalize('https://example.test/another/path'));
        $this->assertSame('http://example.test/test/?query=string', Uri::normalize('http://example.test/path/../test/?query=string'));
    }

    public function testRemoveQueryPreservesTheRestOfTheUri(): void
    {
        $this->assertSame('http://example.test/path/to/resource.jpg', Uri::removeQuery('http://example.test/path/to/resource.jpg?key=value&foo=bar'));
        $this->assertSame('https://example.test/another/path/', Uri::removeQuery('https://example.test/another/path?query=string'));
    }

    public function testRemoveFragmentPreservesTheRestOfTheUri(): void
    {
        $this->assertSame('http://example.test/path/to/resource.jpg', Uri::removeFragment('http://example.test/path/to/resource.jpg#section1'));
        $this->assertSame('https://example.test/another/path/', Uri::removeFragment('https://example.test/another/path#top'));
    }

    public function testRelativeUrisAreResolvedAgainstTheBase(): void
    {
        $this->assertSame('http://example.test/path/to/resource.jpg', Uri::resolveRelative('resource.jpg', 'http://example.test/path/to/'));
        $this->assertSame('https://example.test/path/index.html', Uri::resolveRelative('path/index.html', 'https://example.test/another'));
        $this->assertSame('http://example.test/assets/css/style.css', Uri::resolveRelative('assets/css/style.css', 'http://example.test/'));
    }

    public function testResolveWithFragmentOnly(): void
    {
        $this->assertSame('http://example.test/path/to/resource.jpg#section1', Uri::resolveRelative('#section1', 'http://example.test/path/to/resource.jpg'));
        $this->assertSame('https://example.test/another/path/index.html#top', Uri::resolveRelative('#top', 'https://example.test/another/path/index.html'));
    }

    #[DataProvider('relativeReferenceProvider')]
    public function testRelativeReferencesAreResolvedAgainstTheBaseUri(string $uri, string $base, string $expected): void
    {
        $this->assertSame($expected, Uri::resolveRelative($uri, $base));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function relativeReferenceProvider(): iterable
    {
        yield 'query only' => ['?q=2', 'https://example.test/a/b?q=1', 'https://example.test/a/b/?q=2'];
        yield 'empty reference' => ['', 'https://example.test/a/b?q=1', 'https://example.test/a/b/?q=1'];
        yield 'network path' => ['//other.test/x', 'https://example.test/a/b', 'https://other.test/x'];
        yield 'network path with a base without scheme' => ['//other.test/x', '/blog/', '//other.test/x'];
        yield 'absolute URI' => ['https://other.test/y', 'https://example.test/a/b', 'https://other.test/y'];
        yield 'URI with another scheme' => ['mailto:user@example.test', 'https://example.test/a/b', 'mailto:user@example.test'];
        yield 'relative path' => ['photo.jpg', '/blog/post/', '/blog/post/photo.jpg'];
        yield 'absolute path' => ['/about/photo.jpg', 'http://localhost/blog/', 'http://localhost/about/photo.jpg'];
        yield 'query on a route' => ['?page=2', '/blog/', '/blog/?page=2'];
    }

    public function testEncodePercentEncodesUnsafeCharacters(): void
    {
        $this->assertSame('/path/to/resource?key=%7B%7D&foo=%C3%89%C6%92#%C3%A5n%C2%A9', Uri::encode('/path/to/resource?key={}&foo=Éƒ#ån©'));
    }
}
