<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Files\UploadedFile;
use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\RequestType;
use Formwork\Http\Session\Session;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    #[DataProvider('rootProvider')]
    public function testRootIsTheDirectoryContainingTheFrontController(
        string $scriptName,
        string $expectedRoot,
    ): void {
        $request = $this->request(['SCRIPT_NAME' => $scriptName]);

        $this->assertSame($expectedRoot, $request->root());
    }

    /**
     * @return array<string, array{scriptName: string, expectedRoot: string}>
     */
    public static function rootProvider(): array
    {
        return [
            'document root' => [
                'scriptName'   => '/index.php',
                'expectedRoot' => '/',
            ],
            'nested application' => [
                'scriptName'   => '/formwork/index.php',
                'expectedRoot' => '/formwork/',
            ],
            'absolute cli script path' => [
                'scriptName'   => '/project/vendor/bin/phpunit',
                'expectedRoot' => '/project/vendor/bin/',
            ],
            'missing script name' => [
                'scriptName'   => '',
                'expectedRoot' => '/',
            ],
        ];
    }

    public function testUriRemovesTheScriptDirectoryFromTheRequestUri(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/formwork/index.php',
            'REQUEST_URI' => '/formwork/about',
        ]);

        $this->assertSame('/about', $request->uri());
    }

    public function testUriIsPreservedWhenItDoesNotStartWithTheRequestRoot(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/formwork/index.php',
            'REQUEST_URI' => '/about',
        ]);

        $this->assertSame('/about', $request->uri());
    }

    public function testAbsoluteUriCombinesTheBaseUriAndTheNormalizedRequestUri(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/formwork/index.php',
            'REQUEST_URI' => '/formwork/about',
            'SERVER_NAME' => 'example.test',
            'SERVER_PORT' => '8080',
        ]);

        $this->assertSame('http://example.test:8080/formwork/about', $request->absoluteUri());
    }

    public function testBaseUriIncludesTheApplicationRoot(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/formwork/index.php',
            'SERVER_NAME' => 'Example.test',
            'SERVER_PORT' => '80',
        ]);

        $this->assertSame('http://example.test/formwork/', $request->baseUri());
    }

    public function testBaseUriOfAnInstallationInTheDocumentRootHasNoDoubleSlash(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'example.test',
            'SERVER_PORT' => '80',
        ]);

        $this->assertSame('http://example.test/', $request->baseUri());
    }

    public function testAbsoluteUriOfAnInstallationInTheDocumentRootHasNoDoubleSlash(): void
    {
        $request = $this->request([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/about/',
            'SERVER_NAME' => 'example.test',
            'SERVER_PORT' => '80',
        ]);

        $this->assertSame('http://example.test/about/', $request->absoluteUri());
    }

    public function testBasicAccessorsExposeMethodServerAndRequestHeaders(): void
    {
        $request = $this->request([
            'REQUEST_METHOD'       => 'GET',
            'SERVER_PROTOCOL'      => 'HTTP/2',
            'REMOTE_ADDR'          => '192.0.2.10',
            'CONTENT_LENGTH'       => '42',
            'QUERY_STRING'         => 'page=2',
            'HTTP_REFERER'         => 'https://example.test/source/',
            'HTTP_USER_AGENT'      => 'RequestTest/1.0',
            'HTTP_ACCEPT'          => 'text/html, application/json;q=0.8',
            'HTTP_ACCEPT_ENCODING' => 'gzip, br;q=0.5',
            'HTTP_ACCEPT_LANGUAGE' => 'it-IT, en;q=0.7',
        ]);

        $this->assertSame(RequestMethod::GET, $request->method());
        $this->assertSame('HTTP/2', $request->protocol());
        $this->assertSame('192.0.2.10', $request->ip());
        $this->assertSame(42, $request->contentLength());
        $this->assertSame('https://example.test/source/', $request->referer());
        $this->assertSame('RequestTest/1.0', $request->userAgent());
        $this->assertSame('page=2', $request->content());
        $this->assertSame('text/html', array_key_first($request->mimeTypes()));
        $this->assertSame(0.8, $request->mimeTypes()['application/json']);
        $this->assertSame(['gzip' => 1.0, 'br' => 0.5], $request->encodings());
        $this->assertSame(['it-IT' => 1.0, 'en' => 0.7], $request->languages());
        $this->assertSame('GET', $request->server()->get('REQUEST_METHOD'));
        $this->assertSame('RequestTest/1.0', $request->headers()->get('User-Agent'));
    }

    public function testInputQueryCookiesAndHeaderKeysAreDecodedAndNormalized(): void
    {
        $request = $this->request(
            [
                'HTTP_X_CUSTOM_HEADER' => 'value',
            ],
            ['user%5Bname%5D' => 'Giuseppe'],
            ['page%5Bnumber%5D' => '2'],
            ['cookie%5Bname%5D' => 'session'],
        );

        $this->assertSame(['user[name]' => 'Giuseppe'], $request->input()->toArray());
        $this->assertSame(['page[number]' => '2'], $request->query()->toArray());
        $this->assertSame(['cookie%5Bname%5D' => 'session'], $request->cookies()->toArray());
        $this->assertSame('value', $request->headers()->get('X-Custom-Header'));
        $this->assertNull($request->headers()->get('x_custom_header'));
    }

    public function testHostIsNormalizedAndInvalidHostsAreRejected(): void
    {
        $request = $this->request(['SERVER_NAME' => ' Example.TEST:8080 ']);

        $this->assertSame('example.test', $request->host());
        $this->assertSame(80, $request->port());

        $ipv6Request = $this->request(['SERVER_NAME' => '[2001:db8::1]:8080']);

        $this->assertSame('[2001:db8::1]', $ipv6Request->host());

        $invalidRequest = $this->request(['SERVER_NAME' => 'not a valid host']);

        $this->expectException(UnexpectedValueException::class);
        $invalidRequest->host();
    }

    #[DataProvider('hostileHostProvider')]
    public function testHostHeaderWithPathOrControlCharactersIsRejected(string $host): void
    {
        $request = $this->request(['HTTP_HOST' => $host]);

        $this->expectException(UnexpectedValueException::class);
        $request->host();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileHostProvider(): iterable
    {
        yield 'parent directory' => ['..'];
        yield 'current directory' => ['.'];
        yield 'relative traversal' => ['../../nonexistent/secret'];
        yield 'traversal inside the name' => ['host/../x'];
        yield 'backslash traversal' => ['..\..\x'];
        yield 'encoded traversal' => ['%2e%2e%2f%2e%2e%2fetc'];
        yield 'absolute path' => ['/nonexistent/secret'];
        yield 'path after the host' => ['example.test/path'];
        yield 'userinfo' => ['user@example.test'];
        yield 'query string' => ['ex?ample'];
        yield 'fragment' => ['a#b'];
        yield 'multiple ports' => ['exa:mple:80'];
        yield 'empty port' => ['example.test:'];
        yield 'non numeric port' => ['example.test:abc'];
        yield 'leading dot' => ['.example.test'];
        yield 'leading hyphen' => ['-example.test'];
        yield 'empty label' => ['a..b'];
        yield 'underscore' => ['a_b.test'];
        yield 'whitespace inside' => ['a b'];
        yield 'null byte' => ["a\0b"];
        yield 'newline' => ["a\nb"];
        yield 'comma separated hosts' => ['a.test, b.test'];
        yield 'wildcard' => ['*'];
        yield 'empty' => [''];
        yield 'label longer than 63 characters' => [str_repeat('a', 64) . '.test'];
        yield 'invalid IPv6' => ['[zz]'];
        yield 'non ASCII name' => ['пример.рф'];
    }

    #[DataProvider('validHostProvider')]
    public function testValidHostHeadersAreNormalized(string $host, string $expected): void
    {
        $this->assertSame($expected, $this->request(['HTTP_HOST' => $host])->host());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validHostProvider(): iterable
    {
        yield 'hostname' => ['example.test', 'example.test'];
        yield 'uppercase' => ['EXAMPLE.Test', 'example.test'];
        yield 'surrounding whitespace' => ['  example.test  ', 'example.test'];
        yield 'port' => ['example.test:8080', 'example.test'];
        yield 'subdomain' => ['sub.example.test', 'sub.example.test'];
        yield 'hyphen' => ['my-site.example.test', 'my-site.example.test'];
        yield 'localhost' => ['localhost', 'localhost'];
        yield 'IPv4' => ['192.0.2.1', '192.0.2.1'];
        yield 'IPv4 with a port' => ['192.0.2.1:8080', '192.0.2.1'];
        yield 'IPv6' => ['[2001:db8::1]', '[2001:db8::1]'];
        yield 'IPv6 with a port' => ['[2001:db8::1]:8080', '[2001:db8::1]'];
        yield 'punycode' => ['xn--e1afmkfd.xn--p1ai', 'xn--e1afmkfd.xn--p1ai'];
    }

    public function testHostHeaderTakesPrecedenceOverServerName(): void
    {
        $request = $this->request(['HTTP_HOST' => 'header.test', 'SERVER_NAME' => 'server.test']);

        $this->assertSame('header.test', $request->host());
    }

    public function testInvalidHostHeaderIsNotReplacedByTheServerName(): void
    {
        $request = $this->request(['HTTP_HOST' => '../../etc', 'SERVER_NAME' => 'server.test']);

        $this->expectException(UnexpectedValueException::class);
        $request->host();
    }

    public function testTrustedForwardedHeadersOverrideConnectionMetadata(): void
    {
        $request = $this->request([
            'REMOTE_ADDR'    => '10.0.0.10',
            'HTTP_FORWARDED' => 'for=203.0.113.9;host=Example.org:8443;proto=https;port=8443',
            'SERVER_NAME'    => 'internal.test',
            'SERVER_PORT'    => '80',
            'HTTPS'          => 'off',
        ]);
        $request->setTrustedProxies(['10.0.0.10']);

        $this->assertTrue($request->isFromTrustedProxy());
        $this->assertSame('203.0.113.9', $request->ip());
        $this->assertSame('example.org', $request->host());
        $this->assertSame(8443, $request->port());
        $this->assertTrue($request->isSecure());
    }

    public function testUntrustedForwardedHeadersAreIgnored(): void
    {
        $request = $this->request([
            'REMOTE_ADDR'    => '192.0.2.10',
            'HTTP_FORWARDED' => 'for=203.0.113.9;host=public.test;proto=https;port=8443',
            'SERVER_NAME'    => 'internal.test',
            'SERVER_PORT'    => '8080',
            'HTTPS'          => 'off',
        ]);

        $this->assertFalse($request->isFromTrustedProxy());
        $this->assertSame('192.0.2.10', $request->ip());
        $this->assertSame('internal.test', $request->host());
        $this->assertSame(8080, $request->port());
        $this->assertFalse($request->isSecure());
    }

    public function testXForwardedHeadersAreUsedForTrustedProxies(): void
    {
        $request = $this->request([
            'REMOTE_ADDR'            => '10.0.0.10',
            'HTTP_X_FORWARDED_FOR'   => '203.0.113.9',
            'HTTP_X_FORWARDED_HOST'  => 'public.test:9443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT'  => '9443',
            'SERVER_NAME'            => 'internal.test',
            'SERVER_PORT'            => '80',
        ]);
        $request->setTrustedProxies(['10.0.0.10']);

        $this->assertSame('203.0.113.9', $request->ip());
        $this->assertSame('public.test', $request->host());
        $this->assertSame(9443, $request->port());
        $this->assertTrue($request->isSecure());
    }

    public function testSecurityLocalhostAndRequestTypeAreDetected(): void
    {
        $secure = $this->request([
            'REMOTE_ADDR'           => '127.0.0.1',
            'HTTPS'                 => 'on',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $this->assertTrue($secure->isSecure());
        $this->assertTrue($secure->isLocalhost());
        $this->assertTrue($secure->isXmlHttpRequest());
        $this->assertSame(RequestType::XmlHttpRequest, $secure->type());

        $regular = $this->request(['REMOTE_ADDR' => '192.0.2.10', 'HTTPS' => 'off']);

        $this->assertFalse($regular->isSecure());
        $this->assertFalse($regular->isLocalhost());
        $this->assertFalse($regular->isXmlHttpRequest());
        $this->assertSame(RequestType::Http, $regular->type());
    }

    public function testNonGetContentIsNullWhenTheRequestBodyIsEmpty(): void
    {
        $request = $this->request(['REQUEST_METHOD' => 'POST']);

        $this->assertNull($request->content());
    }

    #[DataProvider('refererProvider')]
    public function testRefererIsValidatedAgainstTheCurrentOriginAndPath(?string $referer, string $path, bool $expected): void
    {
        $request = $this->request(array_filter([
            'SCRIPT_NAME'  => '/index.php',
            'REQUEST_URI'  => '/current',
            'SERVER_NAME'  => 'example.test',
            'HTTP_REFERER' => $referer,
        ], static fn(?string $value): bool => $value !== null));

        $this->assertSame($expected, $request->validateReferer($path));
    }

    /**
     * @return iterable<string, array{?string, string, bool}>
     */
    public static function refererProvider(): iterable
    {
        yield 'page under the path' => ['http://example.test/admin/users?tab=active', '/admin', true];
        yield 'path root with trailing slash' => ['http://example.test/admin/', '/admin', true];
        yield 'path without leading slash' => ['http://example.test/admin/users', 'admin', true];
        yield 'default path accepts any page of the site' => ['http://example.test/anything', '/', true];
        yield 'different path' => ['http://example.test/admin/users', '/panel', false];
        yield 'path sharing only a prefix' => ['http://example.test/admin-evil/users', '/admin', false];
        yield 'different host' => ['http://evil.test/admin/users', '/admin', false];
        yield 'host containing the current one' => ['http://example.test.evil.test/admin/users', '/admin', false];
        yield 'different scheme' => ['https://example.test/admin/users', '/admin', false];
        yield 'missing referer' => [null, '/admin', false];
    }

    public function testUploadedFilesAreNormalizedIntoUploadedFileObjects(): void
    {
        $request = $this->request(
            [],
            [],
            [],
            [],
            [
                'avatar%5Bimage%5D' => [
                    'name'      => 'avatar.png',
                    'full_path' => 'avatar.png',
                    'type'      => 'image/png',
                    'tmp_name'  => '/tmp/php-avatar',
                    'error'     => UPLOAD_ERR_OK,
                    'size'      => '12',
                ],
                'documents' => [
                    'name'      => ['first.txt', 'second.txt'],
                    'full_path' => ['first.txt', 'second.txt'],
                    'type'      => ['text/plain', 'text/plain'],
                    'tmp_name'  => ['/tmp/php-first', '/tmp/php-second'],
                    'error'     => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
                    'size'      => ['10', '0'],
                ],
            ],
        );

        $avatar = $request->files()->get('avatar[image]');
        $documents = $request->files()->get('documents');

        $this->assertInstanceOf(UploadedFile::class, $avatar);
        $this->assertSame('avatar[image]', $avatar->fieldName());
        $this->assertSame('avatar.png', $avatar->clientName());
        $this->assertSame(12, $avatar->size());
        $this->assertTrue($avatar->isUploaded());
        $this->assertIsArray($documents);
        $this->assertCount(2, $documents);
        $this->assertSame('second.txt', $documents[1]->clientName());
        $this->assertTrue($documents[1]->isEmpty());
        $this->assertCount(3, $request->files()->getAll());
    }

    public function testSessionIsMemoizedAndPreviousSessionIsAbsentWithoutCookie(): void
    {
        $request = $this->request();
        $session = $request->session();

        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame($session, $request->session());
        $this->assertFalse($request->hasPreviousSession());

        $session->save();
    }

    public function testFromGlobalsBuildsTheRequestFromThePhpSuperglobals(): void
    {
        $superglobals = [$_SERVER, $_POST, $_GET, $_COOKIE, $_FILES];

        try {
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'SCRIPT_NAME'    => '/formwork/index.php',
                'REQUEST_URI'    => '/formwork/about',
                'SERVER_NAME'    => 'example.test',
                'SERVER_PORT'    => '80',
            ];
            $_POST = ['title' => 'Hello'];
            $_GET = ['page' => '2'];
            $_COOKIE = ['theme' => 'dark'];
            $_FILES = [];

            $request = Request::fromGlobals();
        } finally {
            [$_SERVER, $_POST, $_GET, $_COOKIE, $_FILES] = $superglobals;
        }

        $this->assertSame(RequestMethod::POST, $request->method());
        $this->assertSame('/formwork/', $request->root());
        $this->assertSame('/about', $request->uri());
        $this->assertSame(['title' => 'Hello'], $request->input()->toArray());
        $this->assertSame(['page' => '2'], $request->query()->toArray());
        $this->assertSame(['theme' => 'dark'], $request->cookies()->toArray());
        $this->assertSame([], $request->files()->getAll());
    }

    #[DataProvider('forwardedProtoProvider')]
    public function testForwardedProtoOverridesTheConnectionSchemeForTrustedProxies(string $proto, bool $expectedSecure): void
    {
        foreach ([['HTTPS' => 'on'], ['HTTPS' => 'off'], []] as $connection) {
            $request = $this->request([
                'REMOTE_ADDR'            => '10.0.0.10',
                'HTTP_X_FORWARDED_PROTO' => $proto,
            ] + $connection);
            $request->setTrustedProxies(['10.0.0.10']);

            $this->assertSame($expectedSecure, $request->isSecure(), sprintf('Forwarded proto "%s" with %s', $proto, json_encode($connection)));
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function forwardedProtoProvider(): iterable
    {
        yield 'https' => ['https', true];
        yield 'uppercase https' => ['HTTPS', true];
        yield 'on' => ['on', true];
        yield 'ssl' => ['ssl', true];
        yield 'one' => ['1', true];
        yield 'http' => ['http', false];
        yield 'off' => ['off', false];
        yield 'zero' => ['0', false];
    }

    public function testForwardedProtoIsIgnoredForUntrustedProxies(): void
    {
        $request = $this->request([
            'REMOTE_ADDR'            => '192.0.2.10',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTPS'                  => 'off',
        ]);
        $request->setTrustedProxies(['10.0.0.10']);

        $this->assertFalse($request->isSecure());
    }

    #[DataProvider('localhostProvider')]
    public function testLocalhostIsDetectedFromTheClientIpAddress(string $ip, bool $expected): void
    {
        $this->assertSame($expected, $this->request(['REMOTE_ADDR' => $ip])->isLocalhost());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function localhostProvider(): iterable
    {
        yield 'IPv4 loopback' => ['127.0.0.1', true];
        yield 'IPv6 loopback' => ['::1', true];
        yield 'other loopback address' => ['127.0.0.2', false];
        yield 'private IPv4 address' => ['192.168.1.10', false];
        yield 'documentation IPv4 address' => ['192.0.2.10', false];
        yield 'documentation IPv6 address' => ['2001:db8::1', false];
    }

    /**
     * @param array<string, string> $server
     * @param array<string, mixed>  $input
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $cookies
     * @param array<string, mixed>  $files
     */
    private function request(
        array $server = [],
        array $input = [],
        array $query = [],
        array $cookies = [],
        array $files = [],
    ): Request {
        return new Request(
            $input,
            $query,
            $cookies,
            $files,
            $server + [
                'REQUEST_METHOD' => 'GET',
                'SERVER_NAME'    => 'localhost',
                'SERVER_PORT'    => '80',
            ],
        );
    }
}
