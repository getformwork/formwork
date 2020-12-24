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
    protected $route;

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
     * @param mixed ...$arguments
     */
    public function add(...$arguments): void
    {
        $type = 'HTTP';
        $method = 'GET';
        $callback = null;
        switch (count($arguments)) {
            case 1:
                [$route] = $arguments;
                break;
            case 2:
                [$route, $callback] = $arguments;
                break;
            case 3:
                [$method, $route, $callback] = $arguments;
                break;
            case 4:
                [$type, $method, $route, $callback] = $arguments;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Invalid arguments for %s()', __METHOD__));
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->add($t, $method, $route, $callback);
            }
            return;
        }
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->add($type, $m, $route, $callback);
            }
            return;
        }
        if (!in_array($type, self::REQUEST_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid request type "%s"', $type));
        }
        if (!in_array($method, self::REQUEST_METHODS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s"', $method));
        }
        if (is_array($route)) {
            foreach ($route as $r) {
                $this->add($type, $method, $r, $callback);
            }
            return;
        }
        $this->routes[] = [
            'type'     => $type,
            'method'   => $method,
            'route'    => $route,
            'callback' => $callback
        ];
    }

    /**
     * Dispatch matching route
     */
    public function dispatch()
    {
        foreach ($this->routes as $route) {
            if (HTTPRequest::type() === $route['type'] && HTTPRequest::method() === $route['method'] && $this->match($route['route'])) {
                $this->dispatched = true;
                // Parse Class@method callback syntax
                if (is_string($route['callback']) && Str::contains($route['callback'], '@')) {
                    [$class, $method] = explode('@', $route['callback']);
                    $route['callback'] = [new $class(), $method];
                }
                if (!is_callable($route['callback'])) {
                    throw new UnexpectedValueException(sprintf('Invalid callback for "%s" route', $route['route']));
                }
                return $route['callback']($this->params);
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
        return $this->rewriteRoute($this->route, array_merge($this->params->toArray(), $params));
    }

    /**
     * Match route against request
     */
    protected function match(string $route): bool
    {
        $compiledRoute = $this->compileRoute($route);
        if ($compiledRoute !== false && preg_match($compiledRoute['regex'], $this->request, $matches)) {
            // Remove entire matches from $matches array
            array_shift($matches);
            // Build an associative array using params as keys and matches as values
            $params = array_combine($compiledRoute['params'], $matches);
            $this->route = $route;
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
        return [
            'regex'  => $regex,
            'tokens' => $tokens,
            'params' => $params
        ];
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
