<?php

namespace Formwork\Controllers;

use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Throwable;

interface ErrorsControllerInterface
{
    public function error(ResponseStatus $responseStatus = ResponseStatus::InternalServerError, ?Throwable $throwable = null): Response;
}
