<?php

namespace Formwork\Http;

use Formwork\Utils\Arr;

class HeadersData extends RequestData
{
    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->initialize($data);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function initialize(array $headers): void
    {
        $this->data = Arr::mapKeys($headers, fn (string $key) => str_replace('_', '-', ucwords(strtolower($key), '_')));
        ksort($this->data);
    }
}
