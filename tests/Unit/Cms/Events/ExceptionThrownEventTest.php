<?php

namespace Formwork\Tests\Unit\Cms\Events;

use Exception;
use Formwork\Cms\Events\ExceptionThrownEvent;
use Formwork\Events\Event;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use TypeError;

#[CoversClass(ExceptionThrownEvent::class)]
final class ExceptionThrownEventTest extends TestCase
{
    public function testEventExposesTheThrowableAndTheRequest(): void
    {
        $throwable = new RuntimeException('failure');
        $request = $this->request();

        $event = new ExceptionThrownEvent($throwable, $request);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('exceptionThrown', $event->name());
        $this->assertSame($throwable, $event->throwable());
        $this->assertSame($request, $event->request());
    }

    public function testErrorsAreAcceptedAsThrowables(): void
    {
        $error = new TypeError('wrong type');

        $this->assertSame($error, (new ExceptionThrownEvent($error, $this->request()))->throwable());
    }

    public function testDataContainsTheThrowableAndTheRequest(): void
    {
        $throwable = new Exception('failure');
        $request = $this->request();

        $data = (new ExceptionThrownEvent($throwable, $request))->data();

        $this->assertSame(['throwable' => $throwable, 'request' => $request], $data);
    }

    public function testPropagationCanBeStopped(): void
    {
        $event = new ExceptionThrownEvent(new Exception(), $this->request());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    private function request(): Request
    {
        return new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);
    }
}
