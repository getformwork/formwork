<?php

namespace Formwork\Tests\Unit\Router\Fixtures;

use Formwork\Http\Response;

final class RouteControllerFixture
{
    public function __construct(
        private string $fromRoute = '',
        private string $fromRouteFile = '',
    ) {}

    public function handle(string $fromRouteFile = ''): Response
    {
        return new Response($this->fromRoute . $this->fromRouteFile . $fromRouteFile);
    }
}
