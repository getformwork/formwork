<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

class Uri
{
    use StaticClass;

    /**
     * Default ports which will not be present in generated URI
     */
    protected const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /**
     * Current URI
     */
    protected static ?string $current = null;

    /**
     * Get current URI
     */
    public static function current(): string
    {
        if (!isset(static::$current)) {
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
        $scheme = static::parseComponent($uri, PHP_URL_SCHEME);
        return $scheme !== null ? strtolower($scheme) : null;
    }

    /**
     * Get the host of current or a given URI
     */
    public static function host(string $uri = null): ?string
    {
        if ($uri === null) {
            return strtolower($_SERVER['SERVER_NAME']);
        }
        $host = static::parseComponent($uri, PHP_URL_HOST);
        return $host !== null ? strtolower($host) : null;
    }

    /**
     * Get the port of current or a given URI
     */
    public static function port(string $uri = null): ?int
    {
        if ($uri === null) {
            return (int) $_SERVER['SERVER_PORT'];
        }
        return static::parseComponent($uri, PHP_URL_PORT) ?? static::getDefaultPort(static::scheme($uri));
    }

    /**
     * Return the default port of current URI or a given scheme
     */
    public static function getDefaultPort(string $scheme = null): ?int
    {
        $scheme ??= static::scheme();
        return self::DEFAULT_PORTS[$scheme] ?? null;
    }

    /**
     * Return whether current or a given port is default
     */
    public static function isDefaultPort(int $port = null, string $scheme = null): bool
    {
        $port ??= static::port();
        $scheme ??= static::scheme();
        return $port !== null && $scheme !== null && $port === static::getDefaultPort($scheme);
    }

    /**
     * Get the path of current or a given URI
     */
    public static function path(string $uri = null): ?string
    {
        $uri ??= static::current();
        return static::parseComponent($uri, PHP_URL_PATH);
    }

    /**
     * Get the absolute path of current or a given URI
     */
    public static function absolutePath(string $uri = null): string
    {
        $uri ??= static::current();
        return static::base($uri) . static::path($uri);
    }

    /**
     * Get the query of current or a given URI
     */
    public static function query(string $uri = null): ?string
    {
        $uri ??= static::current();
        return static::parseComponent($uri, PHP_URL_QUERY);
    }

    /**
     * Get the fragment of current or a given URI
     */
    public static function fragment(string $uri = null): ?string
    {
        $uri ??= static::current();
        return static::parseComponent($uri, PHP_URL_FRAGMENT);
    }

    /**
     * Get the base URI (scheme://host:port) of current or a given URI
     */
    public static function base(string $uri = null): string
    {
        $port = static::port($uri);
        return static::scheme($uri) . '://' . static::host($uri) . (static::isDefaultPort($port, static::scheme($uri)) ? '' : ':' . $port);
    }

    /**
     * Convert the query of current or a given URI to array
     */
    public static function queryToArray(string $uri = null): array
    {
        $uri ??= static::current();
        parse_str(static::query($uri), $array);
        return $array;
    }

    /**
     * Parse current or a given URI and get an associative array
     * containing its scheme, host, port, path, query and fragment
     */
    public static function parse(string $uri = null): array
    {
        $uri ??= static::current();
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
        $givenParts = array_keys($parts);
        $parts = array_merge(static::parse($uri), $parts);
        $result = '';
        if (!empty($parts['host'])) {
            $scheme = strtolower($parts['scheme']) ?? 'http';
            $port = $parts['port'] ?? static::getDefaultPort($scheme);
            $result = $scheme . '://' . strtolower($parts['host']);
            if ($forcePort || (in_array('port', $givenParts, true) && !static::isDefaultPort($port, $scheme))) {
                $result .= ':' . $port;
            }
        }
        // Normalize path slashes (leading and trailing separators are trimmed after so that the path
        // is always considered relative and we can then add a trailing slash conditionally)
        $normalizedPath = '/' . trim(Path::normalize($parts['path']), '/');
        // Add trailing slash only if the trailing component is not empty or a filename
        if ($normalizedPath !== '/' && !Str::contains(basename($normalizedPath), '.')) {
            $normalizedPath .= '/';
        }
        $result .= $normalizedPath;
        if (!empty($parts['query'])) {
            $result .= '?' . (is_array($parts['query']) ? http_build_query($parts['query']) : ltrim($parts['query'], '?'));
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
        return Str::append(static::make([], $uri), '/');
    }

    /**
     * Remove query from current or a given URI
     */
    public static function removeQuery(string $uri = null): string
    {
        $uri ??= static::current();
        return static::make(['query' => ''], $uri);
    }

    /**
     * Remove fragment from current or a given URI
     */
    public static function removeFragment(string $uri = null): string
    {
        $uri ??= static::current();
        return static::make(['fragment' => ''], $uri);
    }

    /**
     * Resolve a relative URI against current or a given base URI
     */
    public static function resolveRelative(string $uri, string $base = null): string
    {
        $base ??= static::current();
        if (Str::startsWith($uri, '#')) {
            return static::make(['fragment' => $uri], $base);
        }
        $uriPath = (string) static::path($uri);
        $basePath = (string) static::path($base);
        if (!Str::endsWith($basePath, '/')) {
            $basePath = dirname($basePath);
        }
        return static::make(['path' => Path::resolve($uriPath, $basePath)], $base);
    }

    /**
     * Parse URI component, throwing an exception when the URI is invalid
     */
    protected static function parseComponent(string $uri, int $component)
    {
        $result = parse_url($uri, $component);
        if ($result === false) {
            throw new InvalidArgumentException(sprintf('Invalid URI "%s"', $uri));
        }
        return $result;
    }
}
