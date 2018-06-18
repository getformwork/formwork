<?php

namespace Formwork\Router;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use Exception;

class Router {

    protected $methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE');

    protected $shortcuts = array(
        'num' => '[0-9]+',
        'aln' => '[A-Za-z0-9-]+',
        'all' => '.+'
    );

    protected $routes;

    protected $request;

    protected $params;

    protected $dispatched = false;

    public function __construct($request) {
        $this->request = Uri::normalize($request);
        $this->params = new RouteParams(array());
    }

    protected function compileRoute($route) {
        preg_match_all('/{([A-Za-z0-9_]+)(?::([^{]+))?}/', $route, $matches);
        list($tokens, $params, $patterns) = $matches;
        $regex = $route;
        foreach ($tokens as $i => $token) {
            // Make sure current pattern is not wrapped in a capture group
            $pattern = trim($patterns[$i], '()');
            if (empty($pattern)) $pattern = 'all';
            if (array_key_exists($pattern, $this->shortcuts)) $pattern = $this->shortcuts[$pattern];
            $regex = str_replace($token, '(' . $pattern . ')', $regex);
        }
        // Wrap the regex in tilde delimiters, so we don't need to escape slashes
        $regex = '~^' . trim($regex, '^$') . '$~';
        return array(
            'regex'  => $regex,
            'params' => $params
        );
    }

    public function match($route) {
        $compiledRoute = $this->compileRoute($route);
        if ($compiledRoute !== false && preg_match($compiledRoute['regex'], $this->request, $matches)) {
            // Remove entire matches from $matches array
            array_shift($matches);
            // Build an associative array using params as keys and matches as values
            $params = array_combine($compiledRoute['params'], $matches);
            $this->params = new RouteParams($params);
            return true;
        }
        return false;
    }

    public function add() {
        $method = 'GET';
        $callback = null;
        switch (func_num_args()) {
            case 1:
                $route = func_get_args()[0];
                break;
            case 2:
                list($route, $callback) = func_get_args();
                break;
            case 3:
                list($method, $route, $callback) = func_get_args();
                break;
            default:
                throw new Exception('Invalid arguments for ' . __METHOD__);
        }
        if (is_array($method)) {
            foreach($method as $m) $this->add($m, $route, $callback);
            return;
        }
        if (!in_array($method, $this->methods)) throw new Exception('Invalid HTTP method');
        if (!is_null($callback) && !is_callable($callback)) throw new Exception('Invalid callback');
        if (is_array($route)) {
            foreach($route as $r) $this->add($method, $r, $callback);
            return;
        }
        $this->routes[] = array(
            'method'   => $method,
            'route'    => $route,
            'callback' => $callback
        );
    }

    public function dispatch() {
        foreach ($this->routes as $route) {
            if (HTTPRequest::method() == $route['method'] && $this->match($route['route'])) {
                $this->dispatched = true;
                return call_user_func($route['callback'], $this->params);
            }
        }
    }

    public function hasDispatched() {
        return $this->dispatched;
    }

    public function params() {
        return $this->params;
    }

    public function request() {
        return $this->request;
    }

}
