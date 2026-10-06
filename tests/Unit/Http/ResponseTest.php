<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
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
        $request = $this->request(['REQUEST_METHOD' => 'HEAD']);
        $response = new Response('body', ResponseStatus::OK, ['ETag' => 'tag', 'Content-Length' => '4']);
        $requestWithMatch = $this->request(['HTTP_IF_NONE_MATCH' => 'tag']);

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

    /**
     * @param array<string, string> $server
     */
    private function request(array $server = []): Request
    {
        return new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME'    => 'example.test',
            'SERVER_PORT'    => '80',
        ]);
    }
}
