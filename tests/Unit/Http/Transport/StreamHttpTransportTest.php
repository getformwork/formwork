<?php

namespace Formwork\Tests\Unit\Http\Transport;

use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Http\Transport\StreamHttpTransport;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamHttpTransport::class)]
final class StreamHttpTransportTest extends TestCase
{
    public function testConnectionFailuresExposeTheUriAndThePhpErrors(): void
    {
        // Reserve a free port and release it so that nothing is listening there
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($server);
        $port = substr((string) strrchr((string) stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        $exception = $this->captureException(ConnectionException::class, static fn() => (new StreamHttpTransport())->open('http://127.0.0.1:' . $port, ['http' => ['timeout' => 1, 'ignore_errors' => true]]));
        $this->assertStringContainsString(sprintf('Cannot connect to "http://127.0.0.1:%s"', $port), $exception->getMessage());
        $this->assertStringContainsString('#0 ', $exception->getMessage());
    }

    public function testTheErrorHandlerIsRestoredAfterAFailedConnection(): void
    {
        $handler = static fn(): bool => true;
        set_error_handler($handler);

        $this->captureException(ConnectionException::class, static fn() => (new StreamHttpTransport())->open('http://127.0.0.1:1', ['http' => ['timeout' => 1]]));

        $current = set_error_handler(static fn(): bool => true);
        restore_error_handler();
        restore_error_handler();

        $this->assertSame($handler, $current);
    }
}
