<?php

namespace Formwork\Http;

/**
 * @extends RequestData<array<string, string>>
 */
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
     * Initialize headers data
     *
     * @param array<string, string> $headers
     */
    protected function initialize(array $headers): void
    {
        $this->data = Header::fixHeaderNames($headers);
        ksort($this->data);
    }
}
