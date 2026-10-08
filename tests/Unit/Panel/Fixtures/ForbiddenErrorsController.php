<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;

/**
 * Stands in for the panel errors controller, which needs the whole panel to render its views
 */
final class ForbiddenErrorsController
{
    public function forbidden(): Response
    {
        return new Response('forbidden', ResponseStatus::Forbidden);
    }
}
