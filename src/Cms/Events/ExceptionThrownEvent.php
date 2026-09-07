<?php

namespace Formwork\Cms\Events;

use Formwork\Events\Event;
use Formwork\Http\Request;
use Throwable;

/**
 * @since 2.3.0
 */
class ExceptionThrownEvent extends Event
{
    public function __construct(Throwable $throwable, Request $request)
    {
        parent::__construct('exceptionThrown', ['throwable' => $throwable, 'request' => $request]);
    }

    /**
     * Get the thrown exception
     */
    public function throwable(): Throwable
    {
        return $this->data['throwable'];
    }

    /**
     * Get the request
     */
    public function request(): Request
    {
        return $this->data['request'];
    }
}
