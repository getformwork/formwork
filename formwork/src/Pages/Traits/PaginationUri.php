<?php

namespace Formwork\Pages\Traits;

use Formwork\Formwork;
use Formwork\Router\Route;
use Formwork\Utils\Str;
use RuntimeException;
use UnexpectedValueException;

trait PaginationUri
{
    protected static string $routeParam = 'paginationPage';

    protected static string $routeSuffix = '.pagination';

    protected Route $baseRoute;

    protected Route $paginationRoute;

    public function route(int $pageNumber): string
    {
        if (!$this->has($pageNumber)) {
            throw new UnexpectedValueException(sprintf('Cannot get the route for page %d, the pagination has only %d pages', $pageNumber, $this->length));
        }

        $router = Formwork::instance()->router();

        if ($pageNumber === 1) {
            return $router->generateWith($this->baseRoute()->getName(), []);
        }

        return $router->generateWith($this->paginationRoute()->getName(), [
            static::$routeParam => $pageNumber
        ]);
    }

    public function uri(int $pageNumber): string
    {
        return Formwork::instance()->site()->uri($this->route($pageNumber));
    }

    public function firstPageRoute(): string
    {
        return $this->route($this->firstPage());
    }

    public function firstPageUri(): string
    {
        return $this->uri($this->firstPage());
    }

    public function lastPageRoute(): string
    {
        return $this->route($this->lastPage());
    }

    public function lastPageUri(): string
    {
        return $this->uri($this->lastPage());
    }

    /**
     * Get the route of the next pagination page
     */
    public function previousPageRoute(): string
    {
        return $this->route($this->previousPage());
    }

    /**
     * Get the URI of the next pagination page
     */
    public function previousPageUri(): string
    {
        return $this->uri($this->previousPage());
    }

    /**
     * Get the route of the next pagination page
     */
    public function nextPageRoute(): string
    {
        return $this->route($this->nextPage());
    }

    /**
     * Get the URI of the next pagination page
     */
    public function nextPageUri(): string
    {
        return $this->uri($this->nextPage());
    }

    protected function baseRoute(): ?Route
    {
        if (isset($this->baseRoute)) {
            return $this->baseRoute;
        }

        $router = Formwork::instance()->router();
        $routeName = Str::removeEnd($router->current()->getName(), static::$routeSuffix);

        if (!$router->routes()->has($routeName)) {
            throw new RuntimeException(sprintf('Cannot generate pagination routes, base route "%s" is not defined', $this->routeName));
        }

        return $this->baseRoute = $router->routes()->get($routeName);
    }

    protected function paginationRoute(): ?Route
    {
        if (isset($this->paginationRoute)) {
            return $this->paginationRoute;
        }

        $router = Formwork::instance()->router();
        $routeName = $this->baseRoute()->getName() . static::$routeSuffix;

        if (!$router->routes()->has($routeName)) {
            throw new RuntimeException(sprintf('Cannot generate pagination for route "%s", route "%s" is not defined', $this->baseRoute()->getName(), $routeName));
        }

        return $this->paginationRoute = $router->routes()->get($routeName);
    }
}
