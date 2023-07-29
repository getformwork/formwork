<?php

namespace Formwork\Http;

use Formwork\Utils\Arr;
use Formwork\Utils\Str;

class ServerData extends RequestData
{
    /**
     * @internal
     */
    public function getHeaders(): array
    {
        $headers = Arr::filter($this->data, function ($value, $key) {
            switch (true) {
                case Str::startsWith($key, 'HTTP_'):
                case in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH']):
                    return true;

                default:
                    return false;
            }
        });

        return Arr::mapKeys($headers, fn ($key) => Str::after($key, 'HTTP_'));
    }
}
