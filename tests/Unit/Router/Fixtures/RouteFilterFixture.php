<?php

namespace Formwork\Tests\Unit\Router\Fixtures;

use Formwork\Http\Response;

final class RouteFilterFixture
{
    public function handle(): Response
    {
        return new Response('filter');
    }
}
