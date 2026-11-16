<?php

namespace Formwork\Tests\Unit\Http\Transport;

use Formwork\Http\Exceptions\ConnectionException;
use Formwork\Http\Transport\StreamHttpTransport;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Process\Process;

#[CoversClass(StreamHttpTransport::class)]
#[UsesTempDirectory]
final class StreamHttpTransportTest extends TestCase
{
    public function testConnectionFailuresExposeTheUriAndThePhpErrors(): void
    {
        $port = $this->freePort();

        $connectionException = $this->captureException(ConnectionException::class, static fn() => (new StreamHttpTransport())->open('http://127.0.0.1:' . $port, ['http' => ['timeout' => 1, 'ignore_errors' => true]]));
        $this->assertStringContainsString(sprintf('Cannot connect to "http://127.0.0.1:%s"', $port), $connectionException->getMessage());
        $this->assertStringContainsString('#0 ', $connectionException->getMessage());
    }

    public function testResponsesAreOpenedWithTheirHeaders(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/router.php', '<?php header("X-Test: yes"); echo "body";');
        $port = $this->freePort();

        $process = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", TESTS_TMP_PATH . '/router.php']);
        $process->start();

        try {
            $this->waitForPort($port);

            $connection = (new StreamHttpTransport())->open("http://127.0.0.1:{$port}/", ['http' => ['timeout' => 5]]);

            $this->assertSame('HTTP/1.1 200 OK', $connection['headers'][0]);
            $this->assertContains('X-Test: yes', $connection['headers']);
            $this->assertSame('body', stream_get_contents($connection['handle']));
        } finally {
            $process->stop(0);
        }
    }

    public function testStreamsWithoutHttpHeadersAreRejected(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/file.txt', 'content');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot get headers for "file://' . TESTS_TMP_PATH . '/file.txt"');
        (new StreamHttpTransport())->open('file://' . TESTS_TMP_PATH . '/file.txt', []);
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

    /**
     * Reserve a free port and release it, so that nothing is listening there
     */
    private function freePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($server);
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        return $port;
    }

    /**
     * Wait until a server accepts connections on the given port
     */
    private function waitForPort(int $port): void
    {
        $deadline = microtime(true) + 5;

        while (($socket = @fsockopen('127.0.0.1', $port, timeout: 0.1)) === false) {
            if (microtime(true) > $deadline) {
                $this->fail("Nothing is listening on port {$port}");
            }
            usleep(20_000);
        }

        fclose($socket);
    }
}
