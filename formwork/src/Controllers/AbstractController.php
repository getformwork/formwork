<?php

namespace Formwork\Controllers;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Services\Container;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use Formwork\View\ViewFactory;
use InvalidArgumentException;

abstract class AbstractController
{
    /**
     * Controller name
     */
    protected readonly string $name;

    public function __construct(
        private readonly Container $container,
        protected readonly App $app,
        protected readonly Config $config,
        protected readonly ViewFactory $viewFactory,
        protected readonly Request $request,
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
     * Create a redirect response
     *
     * @param array<string, string> $headers
     */
    protected function redirect(string $route, ResponseStatus $responseStatus = ResponseStatus::Found, array $headers = []): RedirectResponse
    {
        $uri = Uri::make([], Path::join([$this->request->root(), $route]));
        return new RedirectResponse($uri, $responseStatus, $headers);
    }

    /**
     * Create a redirect response to the referer page
     *
     * @param array<string, string> $headers
     */
    protected function redirectToReferer(
        ResponseStatus $responseStatus = ResponseStatus::Found,
        array $headers = [],
        string $default = '/',
        string $base = '/',
    ): RedirectResponse {
        if (
            !in_array($this->request->referer(), [null, $this->request->absoluteUri()], true)
            && $this->request->validateReferer(Path::join([$this->request->root(), $base]))
        ) {
            return new RedirectResponse($this->request->referer(), $responseStatus, $headers);
        }
        return $this->redirect($default, $responseStatus, $headers);
    }

    /**
     * Forward the request to another controller
     *
     * @param class-string         $controller
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
