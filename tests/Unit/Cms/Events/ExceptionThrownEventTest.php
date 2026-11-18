<?php

namespace Formwork\Tests\Unit\Cms\Events;

use Exception;
use Formwork\Cms\Events\ExceptionThrownEvent;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(ExceptionThrownEvent::class)]
final class ExceptionThrownEventTest extends TestCase
{
    public function testEventExposesTheThrowableAndTheRequest(): void
    {
        $runtimeException = new RuntimeException('failure');
        $request = $this->request();

        $exceptionThrownEvent = new ExceptionThrownEvent($runtimeException, $request);

        $this->assertSame('exceptionThrown', $exceptionThrownEvent->name());
        $this->assertSame($runtimeException, $exceptionThrownEvent->throwable());
        $this->assertSame($request, $exceptionThrownEvent->request());
    }

    public function testDataContainsTheThrowableAndTheRequest(): void
    {
        $exception = new Exception('failure');
        $request = $this->request();

        $data = (new ExceptionThrownEvent($exception, $request))->data();

        $this->assertSame(['throwable' => $exception, 'request' => $request], $data);
    }

    private function request(): Request
    {
        return new Request(server: ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);
    }
}
