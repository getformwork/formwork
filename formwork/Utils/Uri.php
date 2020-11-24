<?php

namespace Formwork\Utils;

use InvalidArgumentException;

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
     */
    public static function current(): string
    {
        if (static::$current === null) {
            static::$current = static::base() . rtrim(HTTPRequest::root(), '/') . HTTPRequest::uri();
        }
        return static::$current;
    }

    /**
     * Get the scheme of current or a given URI
     */
    public static function scheme(string $uri = null): ?string
    {
        if ($uri === null) {
            return HTTPRequest::isHTTPS() ? 'https' : 'http';
        }
        return static::parseComponent($uri, PHP_URL_SCHEME);
    }

    /**
     * Get the host of current or a given URI
     */
    public static function host(string $uri = null): ?string
    {
        if ($uri === null) {
            return $_SERVER['SERVER_NAME'];
        }
        return static::parseComponent($uri, PHP_URL_HOST);
    }

    /**
     * Get the port of current or a given URI
     */
    public static function port(string $uri = null): int
    {
        if ($uri === null) {
            return $_SERVER['SERVER_PORT'];
        }
        $port = static::parseComponent($uri, PHP_URL_PORT);
        return $port === null ? 80 : (int) $port;
    }

    /**
     * Return whether current or a given port is default
     *
     * @param int|string|null $port
     */
    public static function isDefaultPort($port = null): bool
    {
        if ($port === null) {
            $port = static::port();
        }
        return in_array((int) $port, self::DEFAULT_PORTS, true);
    }

    /**
     * Get the path of current or a given URI
     */
    public static function path(string $uri = null): ?string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::parseComponent($uri, PHP_URL_PATH);
    }

    /**
     * Get the relative path of current or a given URI
     */
    public static function relativePath(string $uri = null): ?string
    {
        return static::path($uri);
    }

    /**
     * Get the absolute path of current or a given URI
     */
    public static function absolutePath(string $uri = null): string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::base($uri) . static::path($uri);
    }

    /**
     * Get the query of current or a given URI
     */
    public static function query(string $uri = null): ?string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::parseComponent($uri, PHP_URL_QUERY);
    }

    /**
     * Get the fragment of current or a given URI
     */
    public static function fragment(string $uri = null): ?string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::parseComponent($uri, PHP_URL_FRAGMENT);
    }

    /**
     * Get the base URI (scheme://host:port) of current or a given URI
     */
    public static function base(string $uri = null): string
    {
        $uriPort = static::port($uri);
        $port = empty($uriPort) || static::isDefaultPort($uriPort) ? '' : ':' . $uriPort;
        return static::scheme($uri) . '://' . static::host($uri) . $port;
    }

    /**
     * Convert the query of current or a given URI to array
     */
    public static function queryToArray(string $uri = null): array
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
     */
    public static function parse(string $uri = null): array
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
     */
    public static function make(array $parts, string $uri = null, bool $forcePort = false): string
    {
        $parts = array_merge(static::parse($uri), $parts);
        $result = '';
        if (!empty($parts['host'])) {
            $result = empty($parts['scheme']) ? 'http' : $parts['scheme'];
            $result .= '://';
            $result .= strtolower($parts['host']);
            if (!empty($parts['port']) && ($forcePort || !static::isDefaultPort($parts['port']))) {
                $result .= ':' . $parts['port'];
            }
        }
        // Normalize path slashes
        $normalizedPath = '/' . preg_replace('~[/]+~', '/', trim($parts['path'], '/'));
        // Add trailing slash only if the trailing component is not empty or a filename
        if ($normalizedPath !== '/' && !Str::contains(basename($normalizedPath), '.')) {
            $normalizedPath .= '/';
        }
        $result .= $normalizedPath;
        if (!empty($parts['query'])) {
            $result .= '?';
            $result .= is_array($parts['query']) ? http_build_query($parts['query']) : ltrim($parts['query'], '?');
        }
        if (!empty($parts['fragment'])) {
            $result .= '#' . ltrim($parts['fragment'], '#');
        }
        return $result;
    }

    /**
     * Normalize URI fixing required parts and slashes
     */
    public static function normalize(string $uri): string
    {
        // TODO: we should not force trailing slash, avoid this in 2.0
        return rtrim(static::make([], $uri), '/') . '/';
    }

    /**
     * Remove query from current or a given URI
     */
    public static function removeQuery(string $uri = null): string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::make(['query' => ''], $uri);
    }

    /**
     * Remove fragment from current or a given URI
     */
    public static function removeFragment(string $uri = null): string
    {
        if ($uri === null) {
            $uri = static::current();
        }
        return static::make(['fragment' => ''], $uri);
    }

    /**
     * Resolve a relative URI against current or a given base URI
     */
    public static function resolveRelativeUri(string $uri, string $base = null): string
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

    /**
     * Parse URI component, throwing an exception when the URI is invalid
     */
    protected static function parseComponent(string $uri, int $component)
    {
        $result = parse_url($uri, $component);
        if ($result === false) {
            throw new InvalidArgumentException('Invalid URI "' . $uri . '"');
        }
        return $result;
    }
}
