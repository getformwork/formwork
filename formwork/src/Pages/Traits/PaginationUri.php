<?php

namespace Formwork\Pages\Traits;

use Formwork\Cms\Site;
use Formwork\Router\Route;
use Formwork\Router\Router;
use Formwork\Utils\Str;
use RuntimeException;
use UnexpectedValueException;

trait PaginationUri
{
    /**
     * Route param used to get the pagination page
     */
    protected static string $routeParam = 'paginationPage';

    /**
     * Route suffix to make pagination URIs
     */
    protected static string $routeSuffix = '.pagination';

    /**
     * Site instance
     */
    protected Site $site;

    /**
     * Router instance
     */
    protected Router $router;

    /**
     * Base route (without the pagination)
     */
    protected Route $baseRoute;

    /**
     * Pagination route (with the pagination page)
     */
    protected Route $paginationRoute;

    /**
     * Get the route of the given pagination page
     */
    public function route(int $pageNumber): string
    {
        if (!$this->has($pageNumber)) {
            throw new UnexpectedValueException(sprintf('Cannot get the route for page %d, the pagination has only %d pages', $pageNumber, $this->length));
        }

        if ($pageNumber === 1) {
            return $this->router->generateWith($this->baseRoute()->getName(), []);
        }

        return $this->router->generateWith($this->paginationRoute()->getName(), [
            static::$routeParam => $pageNumber,
        ]);
    }

    /**
     * Get the URI of the given pagination page
     */
    public function uri(int $pageNumber): string
    {
        return $this->site->uri($this->route($pageNumber));
    }

    /**
     * Get the route of the first page
     */
    public function firstPageRoute(): string
    {
        return $this->route($this->firstPage());
    }

    /**
     * Get the URI of the first page
     */
    public function firstPageUri(): string
    {
        return $this->uri($this->firstPage());
    }

    /**
     * Get the route of the last page
     */
    public function lastPageRoute(): string
    {
        return $this->route($this->lastPage());
    }

    /**
     * Get the route of the last page
     */
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

    /**
     * Get the base route (without pagination)
     */
    protected function baseRoute(): Route
    {
        if (isset($this->baseRoute)) {
            return $this->baseRoute;
        }

        if (!$this->router->current() instanceof Route) {
            throw new RuntimeException('Cannot generate pagination routes, current route is not defined');
        }

        $routeName = Str::removeEnd($this->router->current()->getName(), static::$routeSuffix);

        if (($baseRoute = $this->router->routes()->get($routeName)) === null) {
            throw new RuntimeException(sprintf('Cannot generate pagination routes, base route "%s" is not defined', $routeName));
        }

        return $this->baseRoute = $baseRoute;
    }

    /**
     * Get the pagination route (with the pagination page)
     */
    protected function paginationRoute(): Route
    {
        if (isset($this->paginationRoute)) {
            return $this->paginationRoute;
        }

        $routeName = $this->baseRoute()->getName() . static::$routeSuffix;

        if (($paginationRoute = $this->router->routes()->get($routeName)) === null) {
            throw new RuntimeException(sprintf('Cannot generate pagination for route "%s", route "%s" is not defined', $this->baseRoute()->getName(), $routeName));
        }

        return $this->paginationRoute = $paginationRoute;
    }
}
