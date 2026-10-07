<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testResponseCanBeSerializedAndRestored(): void
    {
        $response = new Response('body', ResponseStatus::Created, ['X-Test' => 'yes']);
        $restored = Response::fromArray($response->toArray());
        $setState = Response::__set_state($response->toArray());

        $this->assertSame('body', $restored->content());
        $this->assertSame(ResponseStatus::Created, $restored->status());
        $this->assertSame('yes', $restored->headers()->get('X-Test'));
        $this->assertSame($restored->toArray(), $setState->toArray());
    }

    public function testResponsePreparationHandlesHeadAndConditionalRequests(): void
    {
        $request = $this->request(RequestMethod::HEAD);
        $response = new Response('body', ResponseStatus::OK, ['ETag' => 'tag', 'Content-Length' => '4']);
        $requestWithMatch = $this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => 'tag']);

        $response->prepare($request);
        $this->assertSame('', $response->content());
        $response->prepare($requestWithMatch);
        $this->assertSame(ResponseStatus::NotModified, $response->status());
        $this->assertFalse($response->headers()->has('Content-Length'));
    }

    public function testEmptyResponsesClearContentTypeAndContentLength(): void
    {
        foreach ([ResponseStatus::NoContent, ResponseStatus::NotModified] as $status) {
            $response = new Response('body', $status, ['Content-Length' => '4', 'Content-Type' => 'text/plain']);
            $response->prepare($this->request());

            $this->assertSame('', $response->content());
            $this->assertFalse($response->headers()->has('Content-Length'));
            $this->assertFalse($response->headers()->has('Content-Type'));
        }
    }

    public function testResponseSendWritesContentAndAddsDefaultHeaders(): void
    {
        $response = new Response('body');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('body', $output);
        $this->assertSame('text/html; charset=utf-8', $response->headers()->get('Content-Type'));
        $this->assertSame('no-cache, private', $response->headers()->get('Cache-Control'));
    }

    public function testPrepareIsStableWhenAppliedTwiceToTheSameRequest(): void
    {
        $response = new Response('content', ResponseStatus::OK, [
            'ETag'           => '"abc"',
            'Content-Length' => '7',
        ]);
        $request = $this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => '"abc"']);

        $response->prepare($request);
        $first = $response->toArray();
        $response->prepare($request);

        $this->assertSame($first, $response->toArray());
    }

    public function testConditionalNotModifiedResponsesCannotRetainAResponseBody(): void
    {
        $response = new Response('body', ResponseStatus::OK, ['ETag' => '"abc"']);

        $response->prepare($this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => '"abc"']));

        $this->assertSame(ResponseStatus::NotModified, $response->status());
        $this->assertSame('', $response->content());
        $this->assertFalse($response->headers()->has('Content-Type'));
        $this->assertFalse($response->headers()->has('Content-Length'));
    }

    public function testNoContentResponsesRemoveEntityHeadersAndBody(): void
    {
        $response = new Response('body', ResponseStatus::NoContent, [
            'Content-Type'   => 'text/plain',
            'Content-Length' => '4',
        ]);

        $response->prepare($this->request(RequestMethod::GET));

        $this->assertSame('', $response->content());
        $this->assertFalse($response->headers()->has('Content-Type'));
        $this->assertFalse($response->headers()->has('Content-Length'));
    }

    public function testHeadPreparationSuppressesBodyButPreservesEntityMetadata(): void
    {
        $response = new Response('body', ResponseStatus::OK, [
            'Content-Type'   => 'text/plain',
            'Content-Length' => '4',
        ]);

        $response->prepare($this->request(RequestMethod::HEAD));

        $this->assertSame('', $response->content());
        $this->assertSame('text/plain', $response->headers()->get('Content-Type'));
        $this->assertSame('4', $response->headers()->get('Content-Length'));
    }

    public function testSendWritesExactlyThePreparedContent(): void
    {
        $response = new Response('expected');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('expected', $output);
    }

    public function testArrayRoundTripPreservesStatusContentAndHeaders(): void
    {
        $response = new Response('content', ResponseStatus::Created, ['X-Test' => 'value']);

        $this->assertSame($response->toArray(), Response::fromArray($response->toArray())->toArray());
    }

    /**
     * @param array<string, string> $server
     */
    private function request(RequestMethod $method = RequestMethod::GET, array $server = []): Request
    {
        return new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => $method->value,
            'SERVER_NAME'    => 'example.test',
            'SERVER_PORT'    => '80',
        ]);
    }
}
