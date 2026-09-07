<?php

namespace Formwork\Cms\Events;

use Formwork\Events\Event;
use Formwork\Http\Request;
use Formwork\Http\Response;

/**
 * @since 2.3.0
 */
class ResponseBeforeSendEvent extends Event
{
    public function __construct(Response $response, Request $request)
    {
        parent::__construct('responseBeforeSend', ['response' => $response, 'request' => $request]);
    }

    /**
     * Get the response
     */
    public function response(): Response
    {
        return $this->data['response'];
    }

    /**
     * Get the request
     */
    public function request(): Request
    {
        return $this->data['request'];
    }
}
