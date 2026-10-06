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

    public function testNonGetContentReadsTheRequestBodyAndReturnsNullWhenItIsEmpty(): void
    {
        $request = $this->request(['REQUEST_METHOD' => 'POST']);

        $this->assertNull($request->content());
    }

    public function testRefererCanBeValidatedAgainstTheCurrentOriginAndPath(): void
    {
        $request = $this->request([
            'SCRIPT_NAME'  => '/index.php',
            'REQUEST_URI'  => '/current',
            'SERVER_NAME'  => 'example.test',
            'HTTP_REFERER' => 'http://example.test/admin/users?tab=active',
        ]);

        $this->assertTrue($request->validateReferer('/admin'));
        $this->assertFalse($request->validateReferer('/panel'));
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

    public function testFromGlobalsUsesTheScriptNameExposedByThePhpRuntime(): void
    {
        $request = Request::fromGlobals();
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $expectedRoot = '/' . ltrim((string) preg_replace('~[^/]+$~', '', $scriptName), '/');

        $this->assertSame(
            $expectedRoot,
            $request->root(),
            sprintf('Request::root() is derived from SCRIPT_NAME=%s', var_export($scriptName, true)),
        );
    }

    /**
     * @param array<string, string> $server
     */
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
