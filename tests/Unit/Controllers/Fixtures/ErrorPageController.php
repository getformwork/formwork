<?php

namespace Formwork\Tests\Unit\Controllers\Fixtures;

use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;

/**
 * Stands in for the page controller when another controller forwards to its error action
 */
final class ErrorPageController
{
    public function error(): Response
    {
        return new Response('error page', ResponseStatus::NotFound);
    }
}
