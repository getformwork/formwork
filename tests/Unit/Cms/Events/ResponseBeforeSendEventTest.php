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

        $event = new ResponseBeforeSendEvent($response, $request);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('responseBeforeSend', $event->name());
        $this->assertSame($response, $event->response());
        $this->assertSame($request, $event->request());
    }

    public function testListenersCanModifyTheResponse(): void
    {
        $response = new Response('original');
        $event = new ResponseBeforeSendEvent($response, $this->request());

        $event->response()->headers()->set('X-Listener', 'yes');

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
