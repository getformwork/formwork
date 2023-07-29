<?php

namespace Formwork\Http;

use Formwork\Utils\Arr;

class HeadersData extends RequestData
{
    public function __construct(array $data)
    {
        $this->initialize($data);
    }

    protected function initialize(array $headers): void
    {
        $this->data = Arr::mapKeys($headers, fn ($key) => str_replace('_', '-', ucwords(strtolower($key), '_')));
        ksort($this->data);
    }
}
