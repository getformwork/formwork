<?php

namespace Formwork\Utils;

class Uri
{
    /**
     * Current URI
     *
     * @var string
     */
    public static $current = null;

    /**
     * Default ports which will not be present in generated URI
     *
     * @var array
     */
    public static $defaultPorts = array(80, 443);

    /**
     * Get current URI
     *
     * @return string
     */
    public static function current()
    {
        if (is_null(static::$current)) {
            static::$current = static::base() . rtrim(HTTPRequest::root(), '/') . HTTPRequest::uri();
        }
        return static::$current;
    }

    /**
     * Get the scheme of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function scheme($uri = null)
    {
        if (is_null($uri)) {
            return HTTPRequest::isHTTPS() ? 'https' : 'http';
        }
        return parse_url($uri, PHP_URL_SCHEME);
    }

    /**
     * Get the host of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function host($uri = null)
    {
        if (is_null($uri)) {
            return $_SERVER['SERVER_NAME'];
        }
        return parse_url($uri, PHP_URL_HOST);
    }

    /**
     * Get the port of current or a given URI
     *
     * @param string|null $uri
     *
     * @return int
     */
    public static function port($uri = null)
    {
        if (is_null($uri)) {
            return $_SERVER['SERVER_PORT'];
        }
        $port = parse_url($uri, PHP_URL_PORT);
        return is_null($port) ? 80 : (int) $port;
    }

    /**
     * Return whether current or a given port is default
     *
     * @param int|string|null $port
     *
     * @return bool
     */
    public static function isDefaultPort($port = null)
    {
        if (is_null($port)) {
            $port = static::port();
        }
        return in_array((int) $port, static::$defaultPorts, true);
    }

    /**
     * Get the path of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function path($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_PATH);
    }

    /**
     * Get the relative path of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function relativePath($uri = null)
    {
        return static::path($uri);
    }

    /**
     * Get the absolute path of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function absolutePath($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::base($uri) . static::path($uri);
    }

    /**
     * Get the query of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function query($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_QUERY);
    }

    /**
     * Get the fragment of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function fragment($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_FRAGMENT);
    }

    /**
     * Get the base URI (scheme://host:port) of current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function base($uri = null)
    {
        $uriPort = static::port($uri);
        $port = empty($uriPort) || static::isDefaultPort($uriPort) ? '' : ':' . $uriPort;
        return static::scheme($uri) . '://' . static::host($uri) . $port;
    }

    /**
     * Convert the query of current or a given URI to array
     *
     * @param string|null $uri
     *
     * @return array
     */
    public static function queryToArray($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        parse_str(static::query($uri), $array);
        return $array;
    }

    /**
     * Parse current or a given URI and get an associative array
     * containing its scheme, host, port, path, query and fragment
     *
     * @param string|null $uri
     *
     * @return array
     */
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

    /**
     * Make a URI based on the current or a given one using an array with parts
     *
     * @see Uri::parse()
     *
     * @param array       $parts
     * @param string|null $uri
     * @param bool        $forcePort
     *
     * @return string
     */
    public static function make($parts, $uri = null, $forcePort = false)
    {
        $defaults = static::parse($uri);
        $parts = array_merge($defaults, $parts);
        if (!$forcePort && static::isDefaultPort($parts['port'])) {
            $parts['port'] = '';
        }
        $result = empty($parts['scheme']) ? 'http' : $parts['scheme'];
        $result .= '://';
        $result .= strtolower($parts['host']);
        $result .= empty($parts['port']) ? '' : ':' . $parts['port'];
        // If host is empty we reset $result in order to return a relative url
        if (empty($parts['host'])) {
            $result = '';
        }
        $result .= empty($parts['path']) ? '' : '/' . trim($parts['path'], '/');
        if ($parts['path'] !== '/' && strrpos(basename($parts['path']), '.') === false) {
            $result .= '/';
        }
        if (!empty($parts['query'])) {
            $result .= '?';
            $result .= is_array($parts['query']) ? http_build_query($parts['query']) : ltrim($parts['query'], '?');
        }
        $result .= empty($parts['fragment']) ? '' : '#' . ltrim($parts['fragment'], '#');
        return $result;
    }

    /**
     * Normalize URI fixing required parts and leaving only one trailing slash
     *
     * @param string $uri
     *
     * @return string
     */
    public static function normalize($uri)
    {
        if (substr($uri, 0, 7) === 'http://' || substr($uri, 0, 8) === 'https://') {
            return static::make(array(), $uri);
        }
        $normalized = rtrim($uri, '/') . '/';
        return $normalized[0] === '/' ? $normalized : '/' . $normalized;
    }

    /**
     * Remove query from current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function removeQuery($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::make(array('query' => ''), $uri);
    }

    /**
     * Remove fragment from current or a given URI
     *
     * @param string|null $uri
     *
     * @return string
     */
    public static function removeFragment($uri = null)
    {
        if (is_null($uri)) {
            $uri = static::current();
        }
        return static::make(array('fragment' => ''), $uri);
    }

    /**
     * Resolve a relative URI against current or a given base URI
     *
     * @param string      $uri
     * @param string|null $base
     *
     * @return string
     */
    public static function resolveRelativeUri($uri, $base = null)
    {
        if (is_null($base)) {
            $base = static::current();
        }
        if (empty($uri)) {
            return $base;
        }
        if ($uri[0] === '#') {
            return $base . $uri;
        }
        if (!empty(static::host($uri))) {
            return $uri;
        }
        $path = array();
        if ($uri[0] !== '/') {
            $path = explode('/', trim(static::path($base), '/'));
        }
        if (substr($base, -1) !== '/') {
            array_pop($path);
        }
        foreach (explode('/', static::path($uri)) as $segment) {
            if (empty($segment) || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($path);
            } else {
                $path[] = $segment;
            }
        }
        return static::make(array('path' => implode('/', $path)), $base);
    }
}
