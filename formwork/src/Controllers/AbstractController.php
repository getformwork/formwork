<?php

namespace Formwork\Controllers;

use Formwork\App;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Services\Container;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;
use InvalidArgumentException;

abstract class AbstractController
{
    /**
     * Controller name
     */
    protected string $name;

    public function __construct(
        private readonly Container $container,
        protected App $app,
        protected Config $config,
        protected ViewFactory $viewFactory,
        protected Request $request,
    ) {
        $this->name = strtolower(Str::beforeLast(Str::afterLast(static::class, '\\'), 'Controller'));
    }

    /**
     * Render a view
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): string
    {
        return $this->viewFactory->make($name, $data)->render();
    }

    /**
     * Forward the request to another controller
     *
     * @param class-string<self>   $controller
     * @param array<string, mixed> $parameters
     */
    protected function forward(string $controller, string $action, array $parameters = []): Response
    {
        if (!is_subclass_of($controller, AbstractController::class)) {
            throw new InvalidArgumentException(sprintf('Controllers must extend %s', AbstractController::class));
        }
        $instance = $this->container->build($controller);
        return $this->container->call($instance->$action(...), $parameters);
    }
}
