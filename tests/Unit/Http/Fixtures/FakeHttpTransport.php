<?php

namespace Formwork\Tests\Unit\Http\Fixtures;

use Formwork\Http\Transport\HttpTransportInterface;
use LogicException;
use Throwable;

final class FakeHttpTransport implements HttpTransportInterface
{
    /**
     * @var list<array{uri: string, options: array<string, array<string, mixed>>}>
     */
    public array $requests = [];

    /**
     * @var list<array{headers: list<string>, body: string}|Throwable>
     */
    private array $responses = [];

    /**
     * Queue a response, the header lines may contain the whole redirect chain
     *
     * @param list<string> $headers
     */
    public function respond(array $headers, string $body = ''): self
    {
        $this->responses[] = ['headers' => $headers, 'body' => $body];

        return $this;
    }

    /**
     * Queue a failure
     */
    public function fail(Throwable $exception): self
    {
        $this->responses[] = $exception;

        return $this;
    }

    public function open(string $uri, array $contextOptions): array
    {
        $this->requests[] = ['uri' => $uri, 'options' => $contextOptions];

        $response = array_shift($this->responses) ?? throw new LogicException('No response queued for ' . $uri);

        if ($response instanceof Throwable) {
            throw $response;
        }

        $handle = fopen('php://memory', 'w+') ?: throw new LogicException('Unable to open memory stream');
        fwrite($handle, $response['body']);
        rewind($handle);

        return ['handle' => $handle, 'headers' => $response['headers']];
    }

    /**
     * @return array<string, mixed>
     */
    public function lastHttpOptions(): array
    {
        return $this->requests[array_key_last($this->requests) ?? 0]['options']['http'];
    }
}
