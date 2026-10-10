<?php

namespace Formwork\Http;

class RedirectResponse extends Response
{
    public function __construct(string $uri, ResponseStatus $responseStatus = ResponseStatus::Found, array $headers = [])
    {
        parent::__construct('', $responseStatus, [...$headers, 'Location' => $uri]);
    }
}
