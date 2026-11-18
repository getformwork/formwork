<?php

namespace Formwork\Tests\Unit\Http\Fixtures;

use Formwork\Http\Client;
use Formwork\Http\Transport\HttpTransportInterface;

final class ExposedClientFixture extends Client
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, array<string, mixed>>
     */
    public function contextOptions(array $options): array
    {
        return $this->createContextOptions($options);
    }

    public function transport(): HttpTransportInterface
    {
        return $this->transport;
    }

    /**
     * @param list<string> $headers
     *
     * @return array<int, array<string, mixed>>
     */
    public function split(array $headers): array
    {
        return $this->splitHTTPResponseHeader($headers);
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    public function normalize(array $headers): array
    {
        return $this->normalizeHeaders($headers);
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return list<string>
     */
    public function compact(array $headers): array
    {
        return $this->compactHeaders($headers);
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }
}
