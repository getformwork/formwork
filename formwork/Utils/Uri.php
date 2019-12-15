<?php

namespace Formwork\Utils;

class Uri
{
    /**
     * Default ports which will not be present in generated URI
     *
     * @var array
     */
    protected const DEFAULT_PORTS = [80, 443];

    /**
     * Current URI
     *
     * @var string
     */
    protected static $current = null;

    /**
     * Get current URI
     *
     * @return string
     */
    public static function current()
    {
        if (static::$current === null) {
            static::$current = static::base() . rtrim(HTTPRequest::root(), '/') . HTTPRequest::uri();
        }
        return static::$current;
    }

    /**
     * Get the scheme of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function scheme(?string $uri = null)
    {
        if ($uri === null) {
            return HTTPRequest::isHTTPS() ? 'https' : 'http';
        }
        return parse_url($uri, PHP_URL_SCHEME);
    }

    /**
     * Get the host of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function host(?string $uri = null)
    {
        if ($uri === null) {
            return $_SERVER['SERVER_NAME'];
        }
        return parse_url($uri, PHP_URL_HOST);
    }

    /**
     * Get the port of current or a given URI
     *
     * @param ?string $uri
     *
     * @return int
     */
    public static function port(?string $uri = null)
    {
        if ($uri === null) {
            return $_SERVER['SERVER_PORT'];
        }
        $port = parse_url($uri, PHP_URL_PORT);
        return $port === null ? 80 : (int) $port;
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
        if ($port === null) {
            $port = static::port();
        }
        return in_array((int) $port, self::DEFAULT_PORTS, true);
    }

    /**
     * Get the path of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function path(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_PATH);
    }

    /**
     * Get the relative path of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function relativePath(?string $uri = null)
    {
        return static::path($uri);
    }

    /**
     * Get the absolute path of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string
     */
    public static function absolutePath(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::base($uri) . static::path($uri);
    }

    /**
     * Get the query of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function query(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_QUERY);
    }

    /**
     * Get the fragment of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string|null
     */
    public static function fragment(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return parse_url($uri, PHP_URL_FRAGMENT);
    }

    /**
     * Get the base URI (scheme://host:port) of current or a given URI
     *
     * @param ?string $uri
     *
     * @return string
     */
    public static function base(?string $uri = null)
    {
        $uriPort = static::port($uri);
        $port = empty($uriPort) || static::isDefaultPort($uriPort) ? '' : ':' . $uriPort;
        return static::scheme($uri) . '://' . static::host($uri) . $port;
    }

    /**
     * Convert the query of current or a given URI to array
     *
     * @param ?string $uri
     *
     * @return array
     */
    public static function queryToArray(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        parse_str(static::query($uri), $array);
        return $array;
    }

    /**
     * Parse current or a given URI and get an associative array
     * containing its scheme, host, port, path, query and fragment
     *
     * @param ?string $uri
     *
     * @return array
     */
    public static function parse(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return [
            'scheme'   => static::scheme($uri),
            'host'     => static::host($uri),
            'port'     => static::port($uri),
            'path'     => static::path($uri),
            'query'    => static::query($uri),
            'fragment' => static::fragment($uri)
        ];
    }

    /**
     * Make a URI based on the current or a given one using an array with parts
     *
     * @see Uri::parse()
     *
     * @param ?string $uri
     *
     * @return string
     */
    public static function make(array $parts, ?string $uri = null, bool $forcePort = false)
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
     * @return string
     */
    public static function normalize(string $uri)
    {
        if (Str::startsWith($uri, 'http://') || Str::startsWith($uri, 'https://')) {
            return static::make([], $uri);
        }
        $normalized = rtrim($uri, '/') . '/';
        return $normalized[0] === '/' ? $normalized : '/' . $normalized;
    }

    /**
     * Remove query from current or a given URI
     *
     * @param ?string $uri
     *
     * @return string
     */
    public static function removeQuery(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::make(['query' => ''], $uri);
    }

    /**
     * Remove fragment from current or a given URI
     *
     * @param ?string $uri
     *
     * @return string
     */
    public static function removeFragment(?string $uri = null)
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::make(['fragment' => ''], $uri);
    }

    /**
     * Resolve a relative URI against current or a given base URI
     *
     * @param ?string $base
     *
     * @return string
     */
    public static function resolveRelativeUri(string $uri, ?string $base = null)
    {
        if ($base === null) {
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
        $path = [];
        if ($uri[0] !== '/') {
            $path = explode('/', trim(static::path($base), '/'));
        }
        if (!Str::endsWith($base, '/')) {
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
        return static::make(['path' => implode('/', $path)], $base);
    }
}
