<?php

namespace Formwork\Utils;

class HTTPResponse
{
    /**
     * Return an array containing response headers
     */
    public static function headers(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            [$key, $value] = explode(':', $header, 2);
            $headers[$key] = trim($value);
        }
        return $headers;
    }

    /**
     * Clean all output buffers which were not sent
     */
    public static function cleanOutputBuffers(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}
