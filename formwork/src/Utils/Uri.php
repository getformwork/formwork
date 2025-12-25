<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

final class Uri
{
    use StaticClass;

    /**
     * Default ports which will not be present in generated URI
     *
     * @var array<string, int>
     */
    private const array DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    /**
     * Get the scheme of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function scheme(string $uri): ?string
    {
        $scheme = self::parseComponent($uri, PHP_URL_SCHEME);
        return $scheme !== null ? strtolower((string) $scheme) : null;
    }

    /**
     * Get the host of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function host(string $uri): ?string
    {
        $host = self::parseComponent($uri, PHP_URL_HOST);
        return $host !== null ? strtolower((string) $host) : null;
    }

    /**
     * Get the port of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function port(string $uri): ?int
    {
        return self::parseComponent($uri, PHP_URL_PORT);
    }

    /**
     * Return the default port of current URI or a given scheme
     *
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function getDefaultPort(string $scheme): int
    {
        return self::DEFAULT_PORTS[$scheme] ?? throw new InvalidArgumentException(sprintf('Unknown scheme "%s"', $scheme));
    }

    /**
     * Return whether current or a given port is default
     *
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function isDefaultPort(int $port, string $scheme): bool
    {
        return $port === self::getDefaultPort($scheme);
    }

    /**
     * Get the path of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function path(string $uri): ?string
    {
        return self::parseComponent($uri, PHP_URL_PATH);
    }

    /**
     * Get the absolute path of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function absolutePath(string $uri): string
    {
        return self::base($uri) . self::path($uri);
    }

    /**
     * Get the query of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function query(string $uri): ?string
    {
        return self::parseComponent($uri, PHP_URL_QUERY);
    }

    /**
     * Get the fragment of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function fragment(string $uri): ?string
    {
        return self::parseComponent($uri, PHP_URL_FRAGMENT);
    }

    /**
     * Get the base URI (scheme://host:port) of current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    public static function base(string $uri): string
    {
        return sprintf('%s://%s%s', self::scheme($uri), self::host($uri), self::port($uri) !== null ? ':' . self::port($uri) : '');
    }

    /**
     * Convert the query of current or a given URI to array
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     *
     * @return array<array<string>|string>
     */
    public static function queryToArray(string $uri): array
    {
        parse_str(self::query($uri) ?? '', $array);
        return $array;
    }

    /**
     * Parse current or a given URI and get an associative array
     * containing its scheme, host, port, path, query and fragment
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     *
     * @return array{scheme: ?string, host: ?string, port: ?int, path: ?string, query: ?string, fragment: ?string}
     */
    public static function parse(string $uri): array
    {
        return [
            'scheme'   => self::scheme($uri),
            'host'     => self::host($uri),
            'port'     => self::port($uri),
            'path'     => self::path($uri),
            'query'    => self::query($uri),
            'fragment' => self::fragment($uri),
        ];
    }

    /**
     * Make a URI based on the current or a given one using an array with parts
     *
     * @param array{scheme?: string, host?: string, port?: int, path?: string, query?: array<string>|string|null, fragment?: string|null} $parts     Array of URI parts to override
     * @param string                                                                                                                      $uri       The base URI to use
     * @param bool                                                                                                                        $forcePort Whether to always include port in the output even if it's default
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     * @throws InvalidArgumentException If the scheme is unknown
     *
     * @see Uri::parse()
     */
    public static function make(array $parts, string $uri, bool $forcePort = false): string
    {
        $givenParts = array_keys($parts);
        $parts = [...self::parse($uri), ...$parts];
        $result = '';
        if (!empty($parts['host'])) {
            $scheme = strtolower($parts['scheme'] ?? 'http');
            $port = $parts['port'] ?? self::getDefaultPort($scheme);
            $result = $scheme . '://' . strtolower($parts['host']);
            if ($forcePort || (in_array('port', $givenParts, true) && !self::isDefaultPort($port, $scheme))) {
                $result .= ":{$port}";
            }
        }
        // Normalize path slashes (leading and trailing separators are trimmed after so that the path
        // is always considered relative and we can then add a trailing slash conditionally)
        $normalizedPath = '/' . trim(Path::normalize($parts['path'] ?? ''), '/');
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
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function normalize(string $uri): string
    {
        return self::make([], $uri);
    }

    /**
     * Remove query from current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function removeQuery(string $uri): string
    {
        return self::make(['query' => ''], $uri);
    }

    /**
     * Remove fragment from current or a given URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function removeFragment(string $uri): string
    {
        return self::make(['fragment' => ''], $uri);
    }

    /**
     * Resolve a relative URI against current or a given base URI
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     * @throws InvalidArgumentException If the scheme is unknown
     */
    public static function resolveRelative(string $uri, string $base): string
    {
        if (Str::startsWith($uri, '#')) {
            return self::make(['fragment' => $uri], $base);
        }
        $parts = self::parse($uri);
        $basePath = (string) self::path($base);
        if (!Str::endsWith($basePath, '/')) {
            $basePath = dirname($basePath);
        }
        return self::make(
            [
                'path'     => Path::resolve((string) $parts['path'], $basePath),
                'query'    => $parts['query'],
                'fragment' => $parts['fragment'],
            ],
            $base
        );
    }

    /**
     * Encode all characters except those that are allowed by the URI syntax.
     * Compared to `rawurlencode()`, this method encodes less characters
     *
     * @throws InvalidArgumentException If the URI contains malformed data
     */
    public static function encode(string $uri): string
    {
        return preg_replace_callback('~[^A-Za-z0-9-_.!\~*\'()=;/?:@&=+$,#]+~u', fn($m) => rawurlencode($m[0]), $uri)
            ?? throw new InvalidArgumentException(sprintf('Malformed data in URI "%s"', $uri));
    }

    /**
     * Parse URI component, throwing an exception when the URI is invalid
     *
     * @throws InvalidArgumentException If the URI is invalid or contains malformed data
     */
    private static function parseComponent(string $uri, int $component): mixed
    {
        // Avoid altered UTF-8 characters from `parse_url()` output by encoding the URI first
        $result = parse_url(self::encode($uri), $component);
        if ($result === false) {
            throw new InvalidArgumentException(sprintf('Invalid URI "%s"', $uri));
        }
        return is_string($result) ? rawurldecode($result) : $result;
    }
}
