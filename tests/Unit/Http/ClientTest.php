<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Client;
use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Http\ResponseHeaders;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use UnexpectedValueException;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    /**
     * @var resource|null
     */
    private static $server;

    private static int $port;

    public static function setUpBeforeClass(): void
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/http-server.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (!is_resource($process)) {
            self::fail('Cannot start the test HTTP server');
        }

        $port = fgets($pipes[1]);

        if ($port === false || !ctype_digit(trim($port))) {
            proc_terminate($process);
            self::fail('The test HTTP server did not report its port: ' . stream_get_contents($pipes[2]));
        }

        self::$server = $process;
        self::$port = (int) $port;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
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
        $headers = (new Client())->fetchHeaders($this->url('/ok'));

        $this->assertInstanceOf(ResponseHeaders::class, $headers);
        $this->assertSame('value', $headers->get('X-Custom'));
        $this->assertSame('5', $headers->get('Content-Length'));
    }

    public function testFetchReturnsTheBodyStatusAndHeaders(): void
    {
        $response = (new Client())->fetch($this->url('/ok'));

        $this->assertSame('hello', $response->content());
        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('text/plain', $response->headers()->get('Content-Type'));
        $this->assertSame('value', $response->headers()->get('X-Custom'));
    }

    public function testFetchSendsTheConfiguredMethodHeadersAndBody(): void
    {
        $response = (new Client(['headers' => ['X-Default' => 'default']]))->fetch($this->url('/echo'), [
            'method'  => 'POST',
            'content' => 'payload',
            'headers' => ['x-request' => 'per request', 'Content-Length' => '7'],
        ]);

        $request = json_decode($response->content(), true);

        $this->assertSame('POST', $request['method']);
        $this->assertSame('payload', $request['body']);
        $this->assertSame('per request', $request['headers']['X-Request']);
        $this->assertSame('default', $request['headers']['X-Default']);
        $this->assertSame('gzip', $request['headers']['Accept-Encoding']);
        $this->assertSame('close', $request['headers']['Connection']);
    }

    public function testFetchDecodesGzippedContent(): void
    {
        $this->assertSame('compressed content', (new Client())->fetch($this->url('/gzip'))->content());
    }

    public function testFetchRejectsInvalidGzippedContent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot decode gzipped contents');
        (new Client())->fetch($this->url('/bad-gzip'));
    }

    public function testFetchRejectsUnsupportedContentEncodings(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Content-Encoding "deflate"');
        (new Client())->fetch($this->url('/deflate'));
    }

    public function testFetchFollowsRedirectsByDefault(): void
    {
        $response = (new Client())->fetch($this->url('/redirect'));

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('hello', $response->content());
    }

    public function testInvalidUrisAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid URI');
        (new Client())->fetch('not a uri');
    }

    public function testConnectionFailuresExposeTheUri(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($server);
        $port = substr((string) strrchr((string) stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage(sprintf('Cannot connect to "http://127.0.0.1:%s"', $port));
        (new Client(['timeout' => 1]))->fetch('http://127.0.0.1:' . $port);
    }

    public function testConnectionIsClosedUnlessAnotherConnectionHeaderIsGiven(): void
    {
        $default = json_decode((new Client())->fetch($this->url('/echo'))->content(), true);
        $explicit = json_decode((new Client())->fetch($this->url('/echo'), ['headers' => ['connection' => 'keep-alive']])->content(), true);

        $this->assertSame('close', $default['headers']['Connection']);
        $this->assertSame('keep-alive', $explicit['headers']['Connection']);
    }

    public function testHeaderNamesAreNormalizedAndCompactedForTheRequest(): void
    {
        $client = new TestClient();

        $this->assertSame(['X-Test' => 'value', 'Content-Type' => 'text/plain'], $client->normalize(['x-test' => 'value', 'CONTENT-TYPE' => 'text/plain']));
        $this->assertSame(['X-Test: one', 'X-Test: two', 'Accept: text/html'], $client->compact(['X-Test' => ['one', 'two'], ' Accept ' => ' text/html ']));
    }

    public function testContextReflectsTheRequestOptions(): void
    {
        $context = (new TestClient())->context([
            'version'   => 1.0,
            'method'    => 'POST',
            'headers'   => ['X-Test' => 'value'],
            'content'   => 'body',
            'redirects' => ['follow' => false, 'limit' => 3],
            'timeout'   => 7,
            'ssl'       => ['verify' => false, 'cabundle' => null],
        ]);

        $options = stream_context_get_options($context);

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

    public function testContextUsesCaFilesAndCaDirectories(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/bundle.pem', 'bundle');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/certificates');
        $client = new TestClient();

        $file = stream_context_get_options($client->context($this->contextOptions(TESTS_TMP_PATH . '/bundle.pem')));
        $directory = stream_context_get_options($client->context($this->contextOptions(TESTS_TMP_PATH . '/certificates')));

        $this->assertSame(TESTS_TMP_PATH . '/bundle.pem', $file['ssl']['cafile']);
        $this->assertArrayNotHasKey('capath', $file['ssl']);
        $this->assertSame(TESTS_TMP_PATH . '/certificates', $directory['ssl']['capath']);
        $this->assertArrayNotHasKey('cafile', $directory['ssl']);
    }

    public function testMissingCaBundleIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        (new TestClient())->context($this->contextOptions(TESTS_TMP_PATH . '/missing-ca.pem'));
    }

    public function testRedirectsCanBeDisabled(): void
    {
        $response = (new Client(['redirects' => ['follow' => false]]))->fetch($this->url('/redirect'));

        $this->assertSame(ResponseStatus::Found, $response->status());
        $this->assertSame('/ok', $response->headers()->get('Location'));
    }

    public function testRedirectLoopsAreStoppedByTheRedirectLimit(): void
    {
        $response = (new Client(['redirects' => ['limit' => 3]]))->fetch($this->url('/loop'));

        $this->assertSame(ResponseStatus::Found, $response->status());
    }

    public function testErrorResponsesAreReturnedInsteadOfThrown(): void
    {
        $response = (new Client())->fetch($this->url('/missing'));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('nope', $response->content());
    }

    public function testDownloadWritesTheBodyToTheDestination(): void
    {
        $destination = TESTS_TMP_PATH . '/downloaded.txt';

        (new Client())->download($this->url('/ok'), $destination);

        $this->assertSame('hello', FileSystem::read($destination));
        $this->assertSame(['downloaded.txt'], iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH), false));
    }

    public function testIncompleteDownloadsAreRejectedAndLeaveNoFiles(): void
    {
        $destination = TESTS_TMP_PATH . '/truncated.txt';

        try {
            (new Client())->download($this->url('/truncated'), $destination);
            $this->fail('An incomplete download was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Incomplete download', $exception->getMessage());
        }

        $this->assertSame([], iterator_to_array(FileSystem::listFiles(TESTS_TMP_PATH), false));
    }

    private function url(string $path): string
    {
        return 'http://127.0.0.1:' . self::$port . $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function contextOptions(string $cabundle): array
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
     * @return resource
     */
    public function context(array $options): mixed
    {
        return $this->createContext($options);
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
