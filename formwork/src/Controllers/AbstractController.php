<?php

namespace Formwork\Controllers;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Fields\FieldCollection;
use Formwork\Forms\Form;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
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
        protected readonly Request $request,
        protected readonly Router $router,
        protected readonly EventDispatcher $events,
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
        return $this->container->get(ViewFactory::class)->make($name, $data)->render();
    }

    /**
     * Create a new form instance
     *
     * @since 2.3.0
     */
    protected function form(string $name, FieldCollection $fieldCollection): Form
    {
        return $this->container->build(Form::class, ['name' => $name, 'fields' => $fieldCollection]);
    }

    /**
     * Create a redirect response
     *
     * @param array<string, string> $headers
     */
    protected function redirect(string $route, ResponseStatus $responseStatus = ResponseStatus::Found, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($this->app->uri()->path($route), $responseStatus, $headers);
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

    /**
     * Generate a route by name
     *
     * @param array<string, mixed> $params
     */
    protected function generateRoute(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }
}
