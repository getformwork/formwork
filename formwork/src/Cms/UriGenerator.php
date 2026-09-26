<?php

namespace Formwork\Cms;

use Formwork\Http\Request;
use Formwork\Router\Router;
use Formwork\Utils\Path;
use Formwork\Utils\Uri;

class UriGenerator
{
    public function __construct(
        protected Request $request,
        protected Router $router,
    ) {}

    /**
     * Generate a URI for a given path
     *
     * @param string $path Path relative to the application root
     */
    public function path(string $path): string
    {
        return Uri::make([], Path::join([$this->request->root(), $path]));
    }

    /**
     * Generate a URI for a named route
     *
     * @param string               $name   Name of the route
     * @param array<string, mixed> $params Parameters for the route
     */
    public function route(string $name, array $params = []): string
    {
        return $this->path($this->router->generate($name, $params));
    }
}
