<?php

namespace Formwork\Router;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class Router
{
    /**
     * Valid router request types
     *
     * @var array
     */
    protected const REQUEST_TYPES = ['HTTP', 'XHR'];

    /**
     * Valid router request methods
     *
     * @var array
     */
    protected const REQUEST_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Default request type
     *
     * @var string
     */
    protected const DEFAULT_TYPE = 'HTTP';

    /**
     * Default request method
     *
     * @var string
     */
    protected const DEFAULT_METHOD = 'GET';

    /**
     * Array containing route regex shortcuts
     *
     * @var array
     */
    protected const REGEX_SHORTCUTS = [
        'num' => '[0-9]+',
        'aln' => '[A-Za-z0-9-]+',
        'all' => '.+'
    ];

    /**
     * Array containing loaded routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Array containing callbacks to be executed before routes
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The request to match routes against
     *
     * @var string
     */
    protected $request;

    /**
     * Currently matched route
     *
     * @var string
     */
    protected $matchedRoute;

    /**
     * Route params
     *
     * @var RouteParams
     */
    protected $params;

    /**
     * Whether router has dispatched
     *
     * @var bool
     */
    protected $dispatched = false;

    /**
     * Create a new Router instance
     */
    public function __construct(string $request)
    {
        $this->request = Uri::normalize($request);
        $this->params = new RouteParams([]);
    }

    /**
     * Add a route
     *
     * @param array|string    $route
     * @param callable|string $callback
     * @param array|string    $method
     * @param array|string    $type
     */
    public function add($route, $callback, $method = self::DEFAULT_METHOD, $type = self::DEFAULT_TYPE): void
    {
        if (is_array($route)) {
            foreach ($route as $r) {
                $this->add($r, $callback, $method, $type);
            }
            return;
        }
        if (!is_string($route)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only strings and arrays as $route argument', __METHOD__));
        }
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->add($route, $callback, $m, $type);
            }
            return;
        }
        if (!is_string($route)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only strings and arrays as $method argument', __METHOD__));
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->add($route, $callback, $method, $t);
            }
            return;
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only strings and arrays as $type argument', __METHOD__));
        }
        if (!in_array($method, self::REQUEST_METHODS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s"', $method));
        }
        if (!in_array($type, self::REQUEST_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid request type "%s"', $type));
        }
        $this->routes[] = compact('route', 'callback', 'method', 'type');
    }

    /**
     * Add a callback to be executed before routes
     *
     * @param callable|string $callback
     * @param array|string    $method
     * @param array|string    $type
     */
    public function before($callback, $method = self::REQUEST_METHODS, $type = self::REQUEST_TYPES): void
    {
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->before($callback, $m, $type);
            }
            return;
        }
        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only strings and arrays as $method argument', __METHOD__));
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->before($callback, $method, $t);
            }
            return;
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only strings and arrays as $type argument', __METHOD__));
        }
        if (!in_array($method, self::REQUEST_METHODS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s"', $method));
        }
        if (!in_array($type, self::REQUEST_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid request type "%s"', $type));
        }
        $this->beforeCallbacks[] = compact('callback', 'method', 'type');
    }

    /**
     * Dispatch matching route
     */
    public function dispatch()
    {
        foreach ($this->beforeCallbacks as $before) {
            if (HTTPRequest::type() === $before['type'] && HTTPRequest::method() === $before['method']) {
                $beforeCallback = $this->parseCallback($before['callback']);
                if (!is_callable($beforeCallback)) {
                    throw new UnexpectedValueException('Invalid before callback');
                }
                if (($result = $beforeCallback($this->params)) !== null) {
                    $this->dispatched = true;
                    return $result;
                }
            }
        }
        foreach ($this->routes as $route) {
            if (HTTPRequest::type() === $route['type'] && HTTPRequest::method() === $route['method'] && $this->match($route['route'])) {
                $this->dispatched = true;
                $routeCallback = $this->parseCallback($route['callback']);
                if (!is_callable($routeCallback)) {
                    throw new UnexpectedValueException(sprintf('Invalid callback for "%s" route', $route['route']));
                }
                return $routeCallback($this->params);
            }
        }
    }

    /**
     * Return whether router has dispatched
     */
    public function hasDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Get route params
     */
    public function params(): RouteParams
    {
        return $this->params;
    }

    /**
     * Get the request handled by the router
     */
    public function request(): string
    {
        return $this->request;
    }

    /**
     * Rewrite current route
     */
    public function rewrite(array $params): string
    {
        return $this->rewriteRoute($this->matchedRoute, array_merge($this->params->toArray(), $params));
    }

    /**
     * Parse callback
     *
     * @param callable|string $callback
     */
    protected function parseCallback($callback)
    {
        // Parse Class@method callback syntax
        if (is_string($callback) && Str::contains($callback, '@')) {
            [$class, $method] = explode('@', $callback);
            return [new $class(), $method];
        }
        return $callback;
    }

    /**
     * Match route against request
     */
    protected function match(string $route): bool
    {
        $compiledRoute = $this->compileRoute($route);
        if (preg_match($compiledRoute['regex'], $this->request, $matches)) {
            // Remove entire matches from $matches array
            array_shift($matches);
            // Build an associative array using params as keys and matches as values
            $params = array_combine($compiledRoute['params'], $matches);
            $this->matchedRoute = $route;
            $this->params = new RouteParams($params);
            return true;
        }
        return false;
    }

    /**
     * Compile a route to a valid regex and params list
     */
    protected function compileRoute(string $route): array
    {
        preg_match_all('/{([A-Za-z0-9_]+)(?::([^{]+))?}/', $route, $matches);
        [$tokens, $params, $patterns] = $matches;
        $regex = $route;
        foreach ($tokens as $i => $token) {
            // Make sure current pattern is not wrapped in a capture group
            $pattern = trim($patterns[$i], '()');
            if (empty($pattern)) {
                $pattern = 'all';
            }
            if (array_key_exists($pattern, self::REGEX_SHORTCUTS)) {
                $pattern = self::REGEX_SHORTCUTS[$pattern];
            }
            $regex = str_replace($token, '(' . $pattern . ')', $regex);
        }
        // Wrap the regex in tilde delimiters, so we don't need to escape slashes
        $regex = '~^' . trim($regex, '^$') . '$~';
        return compact('regex', 'tokens', 'params');
    }

    /**
     * Rewrite a route given new params
     */
    protected function rewriteRoute(string $route, array $params): string
    {
        $compiledRoute = $this->compileRoute($route);
        foreach ($compiledRoute['params'] as $i => $param) {
            $route = str_replace($compiledRoute['tokens'][$i], $params[$param], $route);
        }
        if (!preg_match($compiledRoute['regex'], $route)) {
            throw new RuntimeException('Cannot rewrite route, one or more params do not match with their regex');
        }
        return $route;
    }
}
