<?php

namespace Formwork\Router;

use Closure;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Parsers\Php;
use Formwork\Router\Exceptions\InvalidRouteException;
use Formwork\Router\Exceptions\RouteNotFoundException;
use Formwork\Services\Container;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;

class Router
{
    /**
     * Valid request types
     */
    protected const REQUEST_TYPES = ['HTTP', 'XHR'];

    /**
     * Valid request methods
     */
    protected const REQUEST_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Valid param separators
     */
    protected const PARAMS_SEPARATORS = '/,;.:-_';

    /**
     * Route params regex
     */
    protected const PARAMS_REGEX = '~([' . self::PARAMS_SEPARATORS . '])?{([A-Za-z0-9_]+)(?::([^{]+))?}(\?)?~';

    /**
     * Array containing route patterns shortcuts
     */
    protected const PATTERN_SHORTCUTS = [
        'num' => '[0-9]+',
        'aln' => '[A-Za-z0-9-]+',
        'all' => '.+',
    ];

    /**
     * Route collection
     */
    protected RouteCollection $routes;

    /**
     * Route filters collection
     */
    protected RouteFilterCollection $filters;

    /**
     * The request to match routes against
     */
    protected string $requestUri;

    /**
     * Currently matched route
     */
    protected ?Route $current;

    /**
     * Route params
     */
    protected RouteParams $params;

    public function __construct(protected Container $container, protected Request $request)
    {
        $this->routes = new RouteCollection();
        $this->filters = new RouteFilterCollection();

        // Ensure requested route is wrapped in slashes
        $this->requestUri = Str::wrap(Uri::path($this->request->uri()), '/');

        $this->params = new RouteParams([]);
    }

    /**
     * Get request
     */
    public function request(): string
    {
        return $this->requestUri;
    }

    public function setRequest(string $request): void
    {
        $this->requestUri = Str::wrap(Uri::path($request), '/');
    }

    /**
     * Add a new route
     */
    public function addRoute(string $name, string $path): Route
    {
        return $this->routes->add(new Route($name, $path));
    }

    /**
     * Return the route collection
     */
    public function routes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Add a new filter
     *
     * @param callable|string $action
     */
    public function addFilter(string $name, $action): RouteFilter
    {
        return $this->filters->add(new RouteFilter($name, $action));
    }

    /**
     * Return the route filter collection
     */
    public function filters(): RouteFilterCollection
    {
        return $this->filters;
    }

    /**
     * Return the current route
     */
    public function current(): ?Route
    {
        return $this->current;
    }

    /**
     * Return the current route params
     */
    public function params(): ?RouteParams
    {
        return $this->params;
    }

    /**
     * Dispatch matching route
     */
    public function dispatch(): Response
    {
        /**
         * @var RouteFilter
         */
        foreach ($this->filters as $filter) {
            if (!$this->matchFilter($filter)) {
                continue;
            }

            $filterCallback = $this->parseAction($filter->getAction());

            if (($result = $this->container->call($filterCallback)) !== null) {
                return $result;
            }
        }

        /**
         * @var Route
         */
        foreach ($this->routes as $route) {
            if (!$this->matchRoute($route)) {
                continue;
            }

            $compiledRoute = $this->compileRoute($route);

            if (preg_match($compiledRoute->regex(), $this->requestUri, $matches, PREG_UNMATCHED_AS_NULL)) {
                // Remove entire matches from $matches array
                array_shift($matches);

                $this->current = $route;

                $this->params = $this->buildParams($compiledRoute->params(), $matches);

                $this->container->define(RouteParams::class, $this->params);

                $routeCallback = $this->parseAction($route->getAction());

                return $this->container->call($routeCallback);
            }
        }

        throw new RouteNotFoundException(sprintf('No route matches with "%s"', $this->requestUri));
    }

    /**
     * Generate a route with given params
     */
    public function generate(string $name, array $params): string
    {
        return $this->generateRoute($this->routes->get($name), $params);
    }

    /**
     * Generate a route with given params overriding the current ones
     */
    public function generateWith(string $name, array $params): string
    {
        return $this->generateRoute($this->routes->get($name), $params + $this->params->toArray());
    }

    /**
     * Rewrite current route with given params
     */
    public function rewrite(array $params): string
    {
        return $this->generateRoute($this->current, $params + $this->params->toArray());
    }

    /**
     * Load routes and filters from file
     */
    public function loadFromFile(string $path, ?string $prefix = null): void
    {
        $data = Php::parseFile($path);

        /**
         * @param Route|RouteFilter $o
         */
        $setProps = static function ($o, array $props) use ($prefix): void {
            if (isset($props['methods'])) {
                $o->methods(...$props['methods']);
            }

            if (isset($props['types'])) {
                $o->types(...$props['types']);
            }

            if ($prefix !== null) {
                $o->prefix($prefix);
            }
        };

        if (isset($data['routes'])) {
            foreach ($data['routes'] as $routeName => $route) {
                $r = $this->addRoute($routeName, $route['path'])
                    ->action($route['action']);
                $setProps($r, $route);
            }
        }

        if (isset($data['filters'])) {
            foreach ($data['filters'] as $filterName => $filter) {
                $f = $this->addFilter($filterName, $filter['action']);
                $setProps($f, $filter);
            }
        }
    }

    /**
     * Compile a route to a valid regex and params list
     */
    protected function compileRoute(Route $route): CompiledRoute
    {
        $path = Str::wrap(Path::join([$route->getPrefix(), $route->getPath()]), '/');

        $params = [];

        $regex = preg_replace_callback(self::PARAMS_REGEX, function (array $matches) use (&$params): string {
            [, $separator, $param, $pattern, $optional] = $matches;

            $this->validateSeparator($separator, $param);

            $this->validateParamName($param, $params);

            $params[] = $param;

            $pattern = $this->resolvePatternShortcut($pattern);

            return sprintf($optional !== null ? '(?:%s(%s))?' : '%s(%s)', preg_quote($separator), $pattern);
        }, $path, -1, $count, PREG_UNMATCHED_AS_NULL);

        // Wrap the regex in tilde delimiters, so we don't need to escape slashes
        $regex = '~^' . trim($regex, '^$') . '$~';

        return new CompiledRoute($path, $regex, $params);
    }

    /**
     * Generate route path with given parameters
     */
    protected function generateRoute(Route $route, array $params): string
    {
        $path = Str::wrap(Path::join([$route->getPrefix(), $route->getPath()]), '/');

        $result = preg_replace_callback(self::PARAMS_REGEX, function (array $matches) use ($params): string {
            [, $separator, $param, $pattern, $optional] = $matches;

            $this->validateSeparator($separator, $param);

            $this->validateParamName($param, $params);

            if (!isset($params[$param])) {
                if ($optional === null) {
                    throw new InvalidArgumentException(sprintf('Non-optional parameter "%s" requires a value to generate route', $param));
                }
                return '';
            }

            $pattern = $this->resolvePatternShortcut($pattern);

            if (!(bool) preg_match('~^' . trim($pattern, '^$') . '$~', $params[$param])) {
                throw new InvalidArgumentException(sprintf('Invalid value for param "%s"', $param));
            }

            return $separator . $params[$param];
        }, $path, -1, $count, PREG_UNMATCHED_AS_NULL);

        return Path::normalize($result);
    }

    /**
     * Build route params
     *
     * @internal
     */
    protected function buildParams(array $names, array $matches): RouteParams
    {
        $params = [];

        // Build an associative array using params as keys and matches as values
        foreach ($matches as $i => $match) {
            if ($match !== null) {
                $param = $names[$i];
                $params[$param] = $match;
            }
        }

        return new RouteParams($params);
    }

    /**
     * Resolve pattern shortcut
     */
    protected function resolvePatternShortcut(?string $pattern): string
    {
        return self::PATTERN_SHORTCUTS[$pattern ?? 'all'] ?? $pattern;
    }

    /**
     * Parse callback
     *
     * @param callable|string $action
     */
    protected function parseAction($action): Closure
    {
        // Parse Class@method callback syntax
        if (is_string($action) && Str::contains($action, '@')) {
            [$controller, $method] = explode('@', $action, 2);
            $class = $this->container->build($controller);
            return $class->$method(...);
        }

        if (is_callable($action)) {
            return Closure::fromCallable($action);
        }

        throw new InvalidRouteException('Invalid callback');
    }

    /**
     * Validate param separator
     *
     * @internal
     */
    protected function validateSeparator(?string $separator, string $param): void
    {
        if ($separator === null) {
            throw new InvalidRouteException(sprintf('Parameter "%s" must be preceded by a separator', $param));
        }
    }

    /**
     * Validate param name
     *
     * @internal
     */
    protected function validateParamName(string $param, array $params): void
    {
        if (in_array($param, $params, true)) {
            throw new InvalidRouteException(sprintf('Parameter "%s" cannot be used more than once', $param));
        }
    }

    /**
     * Match current HTTP method with the given ones
     *
     * @internal
     */
    protected function matchMethods(array $methods): bool
    {
        return in_array($this->request->method()->value, $methods, true);
    }

    /**
     * Match current request type (HTTP, XHR) with the given ones
     *
     * @internal
     */
    protected function matchTypes(array $types): bool
    {
        return in_array($this->request->type()->value, $types, true);
    }

    /**
     * Match prefix with current request
     *
     * @internal
     */
    protected function matchPrefix(?string $prefix): bool
    {
        return $prefix === null || Str::startsWith($this->requestUri, Str::wrap($prefix, '/'));
    }

    /**
     * Match route filter requirements
     *
     * @internal
     */
    protected function matchFilter(RouteFilter $filter): bool
    {
        return $this->matchMethods($filter->getMethods())
            && $this->matchTypes($filter->getTypes())
            && $this->matchPrefix($filter->getPrefix());
    }

    /**
     * Match route requirements
     *
     * @internal
     */
    protected function matchRoute(Route $route): bool
    {
        return $this->matchMethods($route->getMethods())
            && $this->matchTypes($route->getTypes())
            && $this->matchPrefix($route->getPrefix());
    }
}
