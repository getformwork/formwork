<?php

namespace Formwork\Http\Transport;

interface HttpTransportInterface
{
    /**
     * Send a request and return the response stream with its raw header lines
     *
     * The header lines of every response received while following redirects are returned
     * in order, each response starting with its HTTP status line. The context options
     * use the same format of `stream_context_create()`.
     *
     * @param array<string, array<string, mixed>> $contextOptions
     *
     * @return array{handle: resource, headers: list<string>}
     */
    public function open(string $uri, array $contextOptions): array;
}
