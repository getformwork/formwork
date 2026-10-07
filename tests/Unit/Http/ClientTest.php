<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Client;
use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Http\Response;
use Formwork\Http\ResponseHeaders;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use UnexpectedValueException;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function testDefaultsAndHeaderHelpersBuildAnHttpContext(): void
    {
        $client = new TestClient(['timeout' => 3]);

        $this->assertSame(-1, $client->defaults()['timeout']);
        $this->assertSame(['X-Test' => 'value'], $client->normalize(['x-test' => 'value']));
        $this->assertSame(['X-Test: one', 'X-Test: two'], $client->compact(['X-Test' => ['one', 'two']]));
        $this->assertIsResource($client->context([
            'version'   => 1.1,
            'method'    => 'POST',
            'headers'   => ['X-Test' => 'value'],
            'content'   => 'body',
            'redirects' => ['follow' => false, 'limit' => 0],
            'timeout'   => 3,
            'ssl'       => ['verify' => true, 'cabundle' => null],
        ]));
    }

    public function testResponseHeaderParserHandlesRedirectChains(): void
    {
        $responses = (new TestClient())->split([
            'HTTP/1.1 301 Moved Permanently',
            'Location: /next',
            'HTTP/1.1 200 OK',
            'Content-Length: 4',
            'Content-Type: text/plain',
        ]);

        $this->assertSame(301, $responses[0]['statusCode']);
        $this->assertSame('/next', $responses[0]['headers']['Location']);
        $this->assertSame(200, $responses[1]['statusCode']);
    }

    public function testFetchHeadersReturnsResponseHeaders(): void
    {
        $headers = (new HeadersClient())->fetchHeaders('https://example.test/resource');

        $this->assertInstanceOf(ResponseHeaders::class, $headers);
        $this->assertSame('yes', $headers->get('X-Fetched'));
    }

    public function testInvalidUrisAndMalformedResponseHeadersAreRejected(): void
    {
        $client = new TestClient();
        try {
            $client->fetch('not a uri');
            $this->fail('Expected invalid URI exception');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(UnexpectedValueException::class);
        $client->split(['Content-Type: text/plain']);
    }

    public function testConnectionFailuresExposeTheUri(): void
    {
        $client = new TestClient(['timeout' => 1]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Cannot connect to "http://127.0.0.1:1"');
        $client->fetch('http://127.0.0.1:1');
    }

    public function testUnreadableCaBundleIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        (new TestClient())->context([
            'version'   => 1.1,
            'method'    => 'GET',
            'headers'   => [],
            'content'   => '',
            'redirects' => ['follow' => true, 'limit' => 5],
            'timeout'   => -1,
            'ssl'       => ['verify' => true, 'cabundle' => __DIR__ . '/Fixtures/files/missing-ca.pem'],
        ]);
    }
}

class TestClient extends Client
{
    public function context(array $options): mixed
    {
        return $this->createContext($options);
    }

    public function split(array $headers): array
    {
        return $this->splitHTTPResponseHeader($headers);
    }

    public function normalize(array $headers): array
    {
        return $this->normalizeHeaders($headers);
    }

    public function compact(array $headers): array
    {
        return $this->compactHeaders($headers);
    }
}

final class HeadersClient extends TestClient
{
    public function fetch(string $uri, array $options = []): Response
    {
        return new Response('fixture', headers: ['X-Fetched' => 'yes']);
    }
}
