<?php

namespace Formwork\Http;

class RedirectResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(string $uri, ResponseStatus $status = ResponseStatus::Found, array $headers = [])
    {
        $headers += [
            'Location' => $uri,
        ];
        parent::__construct('', $status, $headers);
    }
}
