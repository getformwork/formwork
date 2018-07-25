<?php

namespace Formwork\Utils;

class Uri
{
    public static $current = null;

    public static $defaultPorts = array(80, 443);

    public static function current()
    {
        if (is_null(static::$current)) {
            static::$current = static::base() . rtrim(HTTPRequest::root(), '/') . HTTPRequest::uri();
        }
        return static::$current;
    }

    public static function scheme($uri = null)
    {
        if (is_null($uri)) {
            if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
                return 'https';
            }
            return 'http';
        }
        return parse_url($uri, PHP_URL_SCHEME);
    }

    public static function host($uri = null)
    {
        if (is_null($uri)) {
            return $_SERVER['SERVER_NAME'];
        }
        return parse_url($uri, PHP_URL_HOST);
    }

    public static function port($uri = null)
    {
        if (is_null($uri)) {
            return $_SERVER['SERVER_PORT'];
        }
        $port = parse_url($uri, PHP_URL_PORT);
        return is_null($port) ? 80 : $port;
    }

    public static function defaultPort($port = '')
    {
        if (empty($port)) {
            $port = static::port();
        }
        return in_array($port, static::$defaultPorts);
    }

    public static function path($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_PATH);
    }

    public static function relativePath($uri = null)
    {
        return static::path($uri);
    }

    public static function absolutePath($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::base($uri) . static::path($uri);
    }

    public static function query($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_QUERY);
    }

    public static function fragment($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_FRAGMENT);
    }

    public static function base($uri = null)
    {
        $uriPort = static::port($uri);
        $port = empty($uriPort) || static::defaultPort($uriPort) ? '' : ':' . $uriPort;
        return static::scheme($uri) . '://' . static::host($uri) . $port;
    }

    public static function queryToArray($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        parse_str(static::query($uri), $array);
        return $array;
    }

    public static function parse($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return array(
            'scheme'   => static::scheme($uri),
            'host'     => static::host($uri),
            'port'     => static::port($uri),
            'path'     => static::path($uri),
            'query'    => static::query($uri),
            'fragment' => static::fragment($uri)
        );
    }

    public static function make($parts, $uri = null, $forcePort = false)
    {
        $defaults = static::parse($uri);
        $parts = array_merge($defaults, $parts);
        if (!$forcePort && static::defaultPort($parts['port'])) {
            $parts['port'] = '';
        }
        $result  = empty($parts['scheme']) ? 'http' : $parts['scheme'];
        $result .= '://';
        $result .= strtolower($parts['host']);
        $result .= empty($parts['port']) ? '' : ':' . $parts['port'];
        // If host is empty we reset $result in order to return a relative url
        if (empty($parts['host'])) {
            $result = '';
        }
        $result .= empty($parts['path']) ? '' : '/' . trim($parts['path'], '/');
        if ($parts['path'] != '/' && strrpos(basename($parts['path']), '.') === false) {
            $result .= '/';
        }
        if (!empty($parts['query'])) {
            $result .= '?';
            $result .= is_array($parts['query']) ? http_build_query($parts['query']) : ltrim($parts['query'], '?');
        }
        $result .= empty($parts['fragment']) ? '' : '#' . ltrim($parts['fragment'], '#');
        return $result;
    }

    public static function normalize($uri)
    {
        if (substr($uri, 0, 7) == 'http://' || substr($uri, 0, 8) == 'https://') {
            return static::make(array(), $uri);
        }
        $normalized = rtrim($uri, '/') . '/';
        return $normalized[0] == '/' ? $normalized : '/' . $normalized;
    }

    public static function removeQuery($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::make(array('query' => ''), $uri);
    }

    public static function removeFragment($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::make(array('fragment' => ''), $uri);
    }

    public static function resolveRelativeUri($uri, $base = null)
    {
        if (is_null($base)) {
            $base = static::current();
        }
        if (empty($uri)) {
            return $base;
        }
        if ($uri[0] == '#') {
            return $base . $uri;
        }
        if (!empty(static::host($uri))) {
            return $uri;
        }
        $path = array();
        if ($uri[0] != '/') {
            $path = explode('/', trim(static::path($base), '/'));
        }
        if (count($path) > 0 && $base[strlen($base) - 1] != '/') {
            array_pop($path);
        }
        foreach (explode('/', static::path($uri)) as $segment) {
            if (empty($segment) || $segment == '.') {
                continue;
            }
            if ($segment == '..') {
                if (count($segment) > 0) {
                    array_pop($path);
                }
            } else {
                array_push($path, $segment);
            }
        }
        return Uri::make(array('path' => implode('/', $path)), $base);
    }
}
