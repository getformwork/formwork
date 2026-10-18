<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testResponseCanBeSerializedAndRestored(): void
    {
        $response = new Response('body', ResponseStatus::Created, ['X-Test' => 'yes']);
        $restored = Response::fromArray($response->toArray());
        $setState = Response::__set_state($this->unchecked($response->toArray()));

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

    /**
     * @param array<string, string> $requestHeaders
     */
    #[DataProvider('validatorProvider')]
    public function testConditionalGetRequestsAreAnsweredWithNotModifiedOnlyWhenValidatorsMatch(array $requestHeaders, ResponseStatus $responseStatus): void
    {
        $response = new Response('body', ResponseStatus::OK, ['ETag' => '"abc"', 'Last-Modified' => 'Wed, 01 Jan 2025 00:00:00 GMT']);
        $request = $this->request(RequestMethod::GET, $requestHeaders);

        $response->prepare($request);

        $this->assertSame($responseStatus, $response->status());
    }

    /**
     * @return iterable<string, array{array<string, string>, ResponseStatus}>
     */
    public static function validatorProvider(): iterable
    {
        yield 'matching ETag' => [['HTTP_IF_NONE_MATCH' => '"abc"'], ResponseStatus::NotModified];
        yield 'different ETag' => [['HTTP_IF_NONE_MATCH' => '"other"'], ResponseStatus::OK];
        yield 'ETag in a list' => [['HTTP_IF_NONE_MATCH' => '"other", "abc"'], ResponseStatus::NotModified];
        yield 'weak ETag' => [['HTTP_IF_NONE_MATCH' => 'W/"abc"'], ResponseStatus::NotModified];
        yield 'any ETag' => [['HTTP_IF_NONE_MATCH' => '*'], ResponseStatus::NotModified];
        yield 'matching modification date' => [['HTTP_IF_MODIFIED_SINCE' => 'Wed, 01 Jan 2025 00:00:00 GMT'], ResponseStatus::NotModified];
        yield 'different modification date' => [['HTTP_IF_MODIFIED_SINCE' => 'Tue, 31 Dec 2024 00:00:00 GMT'], ResponseStatus::OK];
        yield 'ETag takes precedence over the modification date' => [
            ['HTTP_IF_NONE_MATCH' => '"other"', 'HTTP_IF_MODIFIED_SINCE' => 'Wed, 01 Jan 2025 00:00:00 GMT'],
            ResponseStatus::OK,
        ];
        yield 'no validators' => [[], ResponseStatus::OK];
    }

    public function testModificationDateIsIgnoredWhenTheRequestHasETagsButTheResponseHasNone(): void
    {
        // If-None-Match takes precedence over If-Modified-Since even when it cannot be evaluated
        $response = new Response('body', ResponseStatus::OK, ['Last-Modified' => 'Wed, 01 Jan 2025 00:00:00 GMT']);

        $response->prepare($this->request(RequestMethod::GET, [
            'HTTP_IF_NONE_MATCH'     => '"abc"',
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 01 Jan 2025 00:00:00 GMT',
        ]));

        $this->assertSame(ResponseStatus::OK, $response->status());
    }

    #[DataProvider('invalidDateProvider')]
    public function testUnparseableModificationDatesNeverProduceNotModified(string $lastModified, string $ifModifiedSince): void
    {
        $response = new Response('body', ResponseStatus::OK, ['Last-Modified' => $lastModified]);

        $response->prepare($this->request(RequestMethod::GET, ['HTTP_IF_MODIFIED_SINCE' => $ifModifiedSince]));

        $this->assertSame(ResponseStatus::OK, $response->status());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDateProvider(): iterable
    {
        yield 'invalid request date' => ['Wed, 01 Jan 2025 00:00:00 GMT', 'not a date'];
        yield 'invalid response date' => ['not a date', 'Wed, 01 Jan 2025 00:00:00 GMT'];
        yield 'both dates invalid' => ['not a date', 'also not a date'];
        yield 'empty request date' => ['Wed, 01 Jan 2025 00:00:00 GMT', ''];
    }

    public function testETagsContainingCommasAreMatchedAsAWhole(): void
    {
        $response = new Response('body', ResponseStatus::OK, ['ETag' => '"a,b"']);

        $response->prepare($this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => '"a,b"']));

        $this->assertSame(ResponseStatus::NotModified, $response->status());
    }

    public function testPartsOfAnETagContainingCommasDoNotMatch(): void
    {
        $response = new Response('body', ResponseStatus::OK, ['ETag' => '"b"']);

        $response->prepare($this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => '"a,b"']));

        $this->assertSame(ResponseStatus::OK, $response->status());
    }

    public function testConditionalHeadersDoNotAffectRequestsOtherThanGetAndHead(): void
    {
        $response = new Response('body', ResponseStatus::OK, ['ETag' => '"abc"']);

        $response->prepare($this->request(RequestMethod::POST, ['HTTP_IF_NONE_MATCH' => '"abc"']));

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('body', $response->content());
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

    // Headers cannot be sent once the output of the test runner has started, so the
    // tests sending a response run in a separate process and check the headers as data

    #[RunInSeparateProcess]
    public function testSendWritesTheContentAndCompletesTheHeaders(): void
    {
        $response = new Response('Hello');

        $this->assertSame('Hello', $this->send($response));
        $this->assertSame('text/html; charset=utf-8', $response->headers()->get('Content-Type'));
        $this->assertSame('no-cache, private', $response->headers()->get('Cache-Control'));
    }

    #[RunInSeparateProcess]
    public function testSendKeepsTheGivenHeaders(): void
    {
        $response = new Response('{}', ResponseStatus::OK, [
            'X-Own'         => 'own',
            'Cache-Control' => 'max-age=60',
            'Content-Type'  => 'application/json',
        ]);

        $this->send($response);

        $this->assertSame('own', $response->headers()->get('X-Own'));
        $this->assertSame('max-age=60', $response->headers()->get('Cache-Control'));
        $this->assertSame('application/json', $response->headers()->get('Content-Type'));
    }

    #[DataProvider('contentTypeProvider')]
    #[RunInSeparateProcess]
    public function testTextContentTypesGetACharsetWhenMissing(string $contentType, string $expected): void
    {
        $response = new Response('body', ResponseStatus::OK, ['Content-Type' => $contentType]);

        $this->send($response);

        $this->assertSame($expected, $response->headers()->get('Content-Type'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function contentTypeProvider(): iterable
    {
        yield 'plain text' => ['text/plain', 'text/plain; charset=utf-8'];
        yield 'CSS' => ['text/css', 'text/css; charset=utf-8'];
        yield 'text with a charset' => ['text/plain; charset=iso-8859-1', 'text/plain; charset=iso-8859-1'];
        yield 'JSON' => ['application/json', 'application/json'];
        yield 'image' => ['image/png', 'image/png'];
    }

    public function testNotModifiedIsOnlyUsedForSuccessfulResponses(): void
    {
        $response = new Response('missing', ResponseStatus::NotFound, ['ETag' => '"abc"']);

        $response->prepare($this->request(RequestMethod::GET, ['HTTP_IF_NONE_MATCH' => '"abc"']));

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertSame('missing', $response->content());
    }

    /**
     * Send the response and return its output
     */
    private function send(Response $response): string
    {
        $response->prepare($this->request());

        ob_start();
        try {
            $response->send();
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @param array<string, string> $server
     */
    private function request(RequestMethod $requestMethod = RequestMethod::GET, array $server = []): Request
    {
        return new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => $requestMethod->value,
            'SERVER_NAME'    => 'example.test',
            'SERVER_PORT'    => '80',
        ]);
    }
}
