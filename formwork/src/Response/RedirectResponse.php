<?php

namespace Formwork\Response;

class RedirectResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(string $uri, int $status = 302, array $headers = [])
    {
        $headers += [
            'Location' => $uri
        ];
        parent::__construct('', $status, $headers);
    }
}
