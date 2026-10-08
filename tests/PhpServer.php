<?php

namespace Formwork\Tests;

use RuntimeException;

/**
 * Runs a router script with the built-in PHP server
 *
 * The CLI SAPI does not keep track of the headers sent by `header()` and `setcookie()`,
 * so the tests that need to observe them ask the `cli-server` SAPI to send a real response.
 */
final class PhpServer
{
    /**
     * @param resource $process
     */
    private function __construct(
        private $process,
        public readonly int $port,
    ) {}

    public static function start(string $routerScript): self
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            throw new RuntimeException('Cannot find a free port for the test server');
        }

        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $routerScript],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start the test server');
        }

        $server = new self($process, $port);

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port);

            if ($connection !== false) {
                fclose($connection);
                return $server;
            }

            usleep(100_000);
        }

        $server->stop();

        throw new RuntimeException('The test server did not start');
    }

    public function stop(): void
    {
        proc_terminate($this->process);
        proc_close($this->process);
    }

    /**
     * Perform a request without following redirects
     *
     * @param array<string, string> $headers
     *
     * @return array{status: int, headers: list<string>, body: string}
     */
    public function request(string $query, array $headers = []): array
    {
        $context = stream_context_create(['http' => [
            'follow_location' => 0,
            'ignore_errors'   => true,
            'timeout'         => 10,
            'header'          => array_map(static fn(string $name, string $value): string => "{$name}: {$value}", array_keys($headers), $headers),
        ]]);

        $body = @file_get_contents(sprintf('http://127.0.0.1:%d/?%s', $this->port, $query), false, $context);

        if ($body === false) {
            throw new RuntimeException('The test server did not respond');
        }

        // @todo use the function directly when we drop support for PHP < 8.4
        $responseHeaders = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);

        return [
            'status'  => (int) explode(' ', $responseHeaders[0] ?? 'HTTP/1.1 0')[1],
            'headers' => array_slice($responseHeaders ?? [], 1),
            'body'    => $body,
        ];
    }
}
