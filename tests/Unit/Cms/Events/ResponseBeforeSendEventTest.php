<?php

namespace Formwork\Tests\Unit\Cms\Events;

use Formwork\Cms\Events\ResponseBeforeSendEvent;
use Formwork\Events\Event;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResponseBeforeSendEvent::class)]
final class ResponseBeforeSendEventTest extends TestCase
{
    public function testEventExposesTheResponseAndTheRequest(): void
    {
        $response = new Response('content');
        $request = $this->request();

        $responseBeforeSendEvent = new ResponseBeforeSendEvent($response, $request);

        $this->assertInstanceOf(Event::class, $responseBeforeSendEvent);
        $this->assertSame('responseBeforeSend', $responseBeforeSendEvent->name());
        $this->assertSame($response, $responseBeforeSendEvent->response());
        $this->assertSame($request, $responseBeforeSendEvent->request());
    }

    public function testListenersCanModifyTheResponse(): void
    {
        $response = new Response('original');
        $responseBeforeSendEvent = new ResponseBeforeSendEvent($response, $this->request());

        $responseBeforeSendEvent->response()->headers()->set('X-Listener', 'yes');

        $this->assertSame('yes', $response->headers()->get('X-Listener'));
    }

    public function testDataContainsTheResponseAndTheRequest(): void
    {
        $response = new Response('content');
        $request = $this->request();

        $this->assertSame(['response' => $response, 'request' => $request], (new ResponseBeforeSendEvent($response, $request))->data());
    }

    private function request(): Request
    {
        return new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);
    }
}
