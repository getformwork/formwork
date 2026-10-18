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
        $runtimeException = new RuntimeException('failure');
        $request = $this->request();

        $exceptionThrownEvent = new ExceptionThrownEvent($runtimeException, $request);

        $this->assertInstanceOf(Event::class, $exceptionThrownEvent);
        $this->assertSame('exceptionThrown', $exceptionThrownEvent->name());
        $this->assertSame($runtimeException, $exceptionThrownEvent->throwable());
        $this->assertSame($request, $exceptionThrownEvent->request());
    }

    public function testErrorsAreAcceptedAsThrowables(): void
    {
        $typeError = new TypeError('wrong type');

        $this->assertSame($typeError, (new ExceptionThrownEvent($typeError, $this->request()))->throwable());
    }

    public function testDataContainsTheThrowableAndTheRequest(): void
    {
        $exception = new Exception('failure');
        $request = $this->request();

        $data = (new ExceptionThrownEvent($exception, $request))->data();

        $this->assertSame(['throwable' => $exception, 'request' => $request], $data);
    }

    public function testPropagationCanBeStopped(): void
    {
        $exceptionThrownEvent = new ExceptionThrownEvent(new Exception(), $this->request());

        $exceptionThrownEvent->stopPropagation();

        $this->assertTrue($exceptionThrownEvent->isPropagationStopped());
    }

    private function request(): Request
    {
        return new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);
    }
}
