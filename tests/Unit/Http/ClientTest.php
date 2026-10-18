<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Client;
use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Http\ResponseHeaders;
use Formwork\Http\ResponseStatus;
use Formwork\Http\Transport\HttpTransportInterface;
use Formwork\Http\Transport\StreamHttpTransport;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Http\Fixtures\FakeHttpTransport;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use UnexpectedValueException;

#[CoversClass(Client::class)]
#[UsesTempDirectory]
final class ClientTest extends TestCase
{
    private FakeHttpTransport $transport;

    protected function setUp(): void
    {
        $this->transport = new FakeHttpTransport();
    }

    public function testDefaultsCanBeOverriddenAndMergedRecursively(): void
    {
        $client = new TestClient(['timeout' => 3, 'headers' => ['X-Test' => 'value'], 'redirects' => ['limit' => 2]]);

        $options = $client->options();

        $this->assertSame(3, $options['timeout']);
        $this->assertSame('value', $options['headers']['X-Test']);
        $this->assertArrayHasKey('User-Agent', $options['headers']);
        $this->assertSame('gzip', $options['headers']['Accept-Encoding']);
        $this->assertSame(['follow' => true, 'limit' => 2], $options['redirects']);
        $this->assertSame(['verify' => true, 'cabundle' => null], $options['ssl']);
        $this->assertSame(-1, $client->defaults()['timeout']);
    }

    public function testResponseHeaderParserHandlesRedirectChains(): void
    {
        $responses = (new TestClient())->split([
            'HTTP/1.1 301 Moved Permanently',
            'location: /next',
            'HTTP/1.1 200 OK',
            'Content-Length: 4',
            'content-type: text/plain',
            'X-Empty:',
        ]);

        $this->assertCount(2, $responses);
        $this->assertSame(301, $responses[0]['statusCode']);
        $this->assertSame('Moved Permanently', $responses[0]['reasonPhrase']);
        $this->assertSame(['Location' => '/next'], $responses[0]['headers']);
        $this->assertSame(200, $responses[1]['statusCode']);
        $this->assertSame('HTTP/1.1', $responses[1]['HTTPVersion']);
        $this->assertSame(['Content-Length' => '4', 'Content-Type' => 'text/plain', 'X-Empty' => ''], $responses[1]['headers']);
    }

    public function testResponseHeadersWithoutAStatusLineAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new TestClient())->split(['Content-Type: text/plain']);
    }

    public function testFetchHeadersReturnsOnlyTheHeaders(): void
    {
        $this->transport->respond($this->ok(['X-Custom: value', 'Content-Length: 5']), 'hello');

        $headers = $this->client()->fetchHeaders('http://example.test/ok');

        $this->assertInstanceOf(ResponseHeaders::class, $headers);
        $this->assertSame('value', $headers->get('X-Custom'));
        $this->assertSame('5', $headers->get('Content-Length'));
        $this->assertSame('HEAD', $this->transport->lastHttpOptions()['method']);
    }

    public function testHeadResponsesDoNotReadTheBody(): void
    {
        $this->transport->respond($this->ok(['Content-Length: 5']), 'hello');

        $response = $this->client()->fetch('http://example.test/ok', ['method' => 'head']);

        $this->assertSame('', $response->content());
    }

    public function testFetchReturnsTheBodyStatusAndHeaders(): void
    {
        $this->transport->respond($this->ok(['Content-Type: text/plain', 'x-custom: value', 'Content-Length: 5']), 'hello');

        $response = $this->client()->fetch('http://example.test/ok');

        $this->assertSame('hello', $response->content());
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('text/plain', $response->headers()->get('Content-Type'));
        $this->assertSame('value', $response->headers()->get('X-Custom'));
        $this->assertSame('http://example.test/ok', $this->transport->requests[0]['uri']);
    }

    public function testFetchReadsOnlyAsManyBytesAsContentLength(): void
    {
        $this->transport->respond($this->ok(['Content-Length: 5']), 'hello and trailing garbage');

        $this->assertSame('hello', $this->client()->fetch('http://example.test/')->content());
    }

    public function testFetchReadsTheWholeBodyWithoutContentLength(): void
    {
        $this->transport->respond($this->ok(), 'hello world');

        $this->assertSame('hello world', $this->client()->fetch('http://example.test/')->content());
    }

    public function testFetchSendsTheConfiguredMethodHeadersAndBody(): void
    {
        $this->transport->respond($this->ok());

        $this->client(['headers' => ['X-Default' => 'default']])->fetch('http://example.test/echo', [
            'method'  => 'POST',
            'content' => 'payload',
            'headers' => ['x-request' => 'per request', 'Content-Length' => '7'],
        ]);

        $http = $this->transport->lastHttpOptions();

        $this->assertSame('POST', $http['method']);
        $this->assertSame('payload', $http['content']);
        $this->assertContains('X-Request: per request', $http['header']);
        $this->assertContains('X-Default: default', $http['header']);
        $this->assertContains('Accept-Encoding: gzip', $http['header']);
        $this->assertContains('Content-Length: 7', $http['header']);
        $this->assertContains('Connection: close', $http['header']);
    }

    public function testFetchDecodesGzippedContent(): void
    {
        $this->transport->respond($this->ok(['Content-Encoding: gzip']), (string) gzencode('compressed content'));

        $this->assertSame('compressed content', $this->client()->fetch('http://example.test/gzip')->content());
    }

    public function testFetchRejectsInvalidGzippedContent(): void
    {
        $this->transport->respond($this->ok(['Content-Encoding: gzip']), 'this is not gzip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot decode gzipped contents');
        $this->client()->fetch('http://example.test/bad-gzip');
    }

    public function testFetchRejectsUnsupportedContentEncodings(): void
    {
        $this->transport->respond($this->ok(['Content-Encoding: deflate']), (string) gzdeflate('content'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Content-Encoding "deflate"');
        $this->client()->fetch('http://example.test/deflate');
    }

    public function testTheLastResponseOfARedirectChainIsReturned(): void
    {
        $this->transport->respond([
            'HTTP/1.1 302 Found',
            'Location: /ok',
            'Content-Length: 0',
            'HTTP/1.1 200 OK',
            'Content-Length: 5',
        ], 'hello');

        $response = $this->client()->fetch('http://example.test/redirect');

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('hello', $response->content());
        $this->assertSame('5', $response->headers()->get('Content-Length'));
        $this->assertNull($response->headers()->get('Location'));
    }

    public function testRedirectsAreFollowedByDefault(): void
    {
        $this->transport->respond($this->ok());

        $this->client()->fetch('http://example.test/redirect');

        $this->assertSame(1, $this->transport->lastHttpOptions()['follow_location']);
        $this->assertSame(5, $this->transport->lastHttpOptions()['max_redirects']);
    }

    public function testRedirectsCanBeDisabled(): void
    {
        $this->transport->respond(['HTTP/1.1 302 Found', 'Location: /ok']);

        $response = $this->client(['redirects' => ['follow' => false]])->fetch('http://example.test/redirect');

        $this->assertSame(0, $this->transport->lastHttpOptions()['follow_location']);
        $this->assertSame(ResponseStatus::Found, $response->status());
        $this->assertSame('/ok', $response->headers()->get('Location'));
    }

    public function testRedirectLimitCanBeConfiguredPerRequest(): void
    {
        $this->transport->respond($this->ok());

        $this->client()->fetch('http://example.test/loop', ['redirects' => ['limit' => 3]]);

        $this->assertSame(3, $this->transport->lastHttpOptions()['max_redirects']);
    }

    public function testInvalidUrisAreRejectedBeforeTouchingTheTransport(): void
    {
        $exception = $this->captureException(InvalidArgumentException::class, fn() => $this->client()->fetch('not a uri'));
        $this->assertStringContainsString('invalid URI', $exception->getMessage());

        $this->assertSame([], $this->transport->requests);
    }

    public function testTransportFailuresArePropagated(): void
    {
        $this->transport->fail(new ConnectionException('Cannot connect to "http://example.test"'));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Cannot connect to "http://example.test"');
        $this->client()->fetch('http://example.test');
    }

    public function testResponsesWithoutHeadersAreRejected(): void
    {
        $this->transport->respond([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot get current response');
        $this->client()->fetch('http://example.test');
    }

    public function testMalformedResponseHeadersAreRejected(): void
    {
        $this->transport->respond(['Content-Type: text/plain']);

        $this->expectException(UnexpectedValueException::class);
        $this->client()->fetch('http://example.test');
    }

    public function testConnectionIsClosedUnlessAnotherConnectionHeaderIsGiven(): void
    {
        $this->transport->respond($this->ok())->respond($this->ok())->respond($this->ok());

        $this->client()->fetch('http://example.test/');
        $default = $this->transport->lastHttpOptions()['header'];
        $this->client()->fetch('http://example.test/', ['headers' => ['connection' => 'keep-alive']]);
        $explicit = $this->transport->lastHttpOptions()['header'];
        $this->client(['version' => 1.0])->fetch('http://example.test/');
        $http10 = $this->transport->lastHttpOptions()['header'];

        $this->assertContains('Connection: close', $default);
        $this->assertContains('Connection: keep-alive', $explicit);
        $this->assertNotContains('Connection: close', $explicit);
        $this->assertNotContains('Connection: close', $http10);
    }

    public function testHeaderNamesAreNormalizedAndCompactedForTheRequest(): void
    {
        $client = new TestClient();

        $this->assertSame(['X-Test' => 'value', 'Content-Type' => 'text/plain'], $client->normalize(['x-test' => 'value', 'CONTENT-TYPE' => 'text/plain']));
        $this->assertSame(['X-Test: one', 'X-Test: two', 'Accept: text/html'], $client->compact(['X-Test' => ['one', 'two'], ' Accept ' => ' text/html ']));
    }

    public function testContextOptionsReflectTheRequestOptions(): void
    {
        $context = (new TestClient())->contextOptions([
            'version'   => 1.0,
            'method'    => 'POST',
            'headers'   => ['X-Test' => 'value'],
            'content'   => 'body',
            'redirects' => ['follow' => false, 'limit' => 3],
            'timeout'   => 7,
            'ssl'       => ['verify' => false, 'cabundle' => null],
        ]);

        $options = $context;

        $this->assertSame('POST', $options['http']['method']);
        $this->assertSame(1.0, $options['http']['protocol_version']);
        $this->assertSame(['X-Test: value'], $options['http']['header']);
        $this->assertSame('body', $options['http']['content']);
        $this->assertSame(0, $options['http']['follow_location']);
        $this->assertSame(3, $options['http']['max_redirects']);
        $this->assertSame(7, $options['http']['timeout']);
        $this->assertTrue($options['http']['ignore_errors']);
        $this->assertFalse($options['ssl']['verify_peer']);
        $this->assertFalse($options['ssl']['verify_peer_name']);
        $this->assertFalse($options['ssl']['allow_self_signed']);
    }

    public function testContextOptionsUseCaFilesAndCaDirectories(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/bundle.pem', 'bundle');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/certificates');
        $client = new TestClient();

        $file = $client->contextOptions($this->requestOptions(TESTS_TMP_PATH . '/bundle.pem'));
        $directory = $client->contextOptions($this->requestOptions(TESTS_TMP_PATH . '/certificates'));

        $this->assertSame(TESTS_TMP_PATH . '/bundle.pem', $file['ssl']['cafile']);
        $this->assertArrayNotHasKey('capath', $file['ssl']);
        $this->assertSame(TESTS_TMP_PATH . '/certificates', $directory['ssl']['capath']);
        $this->assertArrayNotHasKey('cafile', $directory['ssl']);
    }

    public function testMissingCaBundleIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        (new TestClient())->contextOptions($this->requestOptions(TESTS_TMP_PATH . '/missing-ca.pem'));
    }

    public function testErrorResponsesAreReturnedInsteadOfThrown(): void
    {
        $this->transport->respond(['HTTP/1.1 404 Not Found', 'Content-Type: text/plain', 'Content-Length: 4'], 'nope');

        $response = $this->client()->fetch('http://example.test/missing');

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('nope', $response->content());
        $this->assertTrue($this->transport->lastHttpOptions()['ignore_errors']);
    }

    public function testDownloadWritesTheBodyToTheDestination(): void
    {
        $destination = TESTS_TMP_PATH . '/downloaded.txt';
        $this->transport->respond($this->ok(['Content-Length: 5']), 'hello');

        $this->client()->download('http://example.test/ok', $destination);

        $this->assertSame('hello', FileSystem::read($destination));
        $this->assertSame(['downloaded.txt'], iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH), false));
    }

    public function testDownloadsWithoutContentLengthAreCopiedUntilTheEnd(): void
    {
        $destination = TESTS_TMP_PATH . '/downloaded.txt';
        $this->transport->respond($this->ok(), 'hello world');

        $this->client()->download('http://example.test/ok', $destination);

        $this->assertSame('hello world', FileSystem::read($destination));
    }

    public function testIncompleteDownloadsAreRejectedAndLeaveNoFiles(): void
    {
        $destination = TESTS_TMP_PATH . '/truncated.txt';
        $this->transport->respond($this->ok(['Content-Length: 10']), 'short');

        $exception = $this->captureException(RuntimeException::class, fn() => $this->client()->download('http://example.test/truncated', $destination));
        $this->assertStringContainsString('Incomplete download', $exception->getMessage());
        $this->assertStringContainsString('expected 10 bytes, got 5', $exception->getMessage());

        $this->assertSame([], iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH), false));
    }

    public function testFailedDownloadsDoNotReplaceAnExistingFile(): void
    {
        $destination = TESTS_TMP_PATH . '/existing.txt';
        FileSystem::write($destination, 'previous');
        $this->transport->respond($this->ok(['Content-Length: 10']), 'short');

        $this->captureException(RuntimeException::class, fn() => $this->client()->download('http://example.test/truncated', $destination));

        $this->assertSame('previous', FileSystem::read($destination));
        $this->assertSame(['existing.txt'], iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH), false));
    }

    public function testTheDefaultTransportUsesPhpStreams(): void
    {
        $client = new TestClient();

        $this->assertInstanceOf(StreamHttpTransport::class, $client->transport());
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string>
     */
    private function ok(array $headers = []): array
    {
        return ['HTTP/1.1 200 OK', ...$headers];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function client(array $options = []): Client
    {
        return new Client($options, $this->transport);
    }

    private function requestOptions(string $cabundle): array
    {
        return [
            'version'   => 1.1,
            'method'    => 'GET',
            'headers'   => [],
            'content'   => '',
            'redirects' => ['follow' => true, 'limit' => 5],
            'timeout'   => -1,
            'ssl'       => ['verify' => true, 'cabundle' => $cabundle],
        ];
    }
}

class TestClient extends Client
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, array<string, mixed>>
     */
    public function contextOptions(array $options): array
    {
        return $this->createContextOptions($options);
    }

    public function transport(): HttpTransportInterface
    {
        return $this->transport;
    }

    /**
     * @param list<string> $headers
     *
     * @return array<int, array<string, mixed>>
     */
    public function split(array $headers): array
    {
        return $this->splitHTTPResponseHeader($headers);
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    public function normalize(array $headers): array
    {
        return $this->normalizeHeaders($headers);
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return list<string>
     */
    public function compact(array $headers): array
    {
        return $this->compactHeaders($headers);
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }
}
