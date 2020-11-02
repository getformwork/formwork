<?php

namespace Formwork\Utils;

class HTTPRequest
{
    /**
     * Localhost IP addresses
     *
     * @var array
     */
    protected const LOCALHOST_IP_ADDRESSES = ['127.0.0.1', '::1'];

    /**
     * Array containing HTTP request headers
     *
     * @var array
     */
    protected static $headers = [];

    /**
     * Array containing HTTP request files
     *
     * @var array
     */
    protected static $files = [];

    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get request type (HTTP or XHR)
     */
    public static function type(): string
    {
        return static::isXHR() ? 'XHR' : 'HTTP';
    }

    /**
     * Get request URI
     */
    public static function uri(): string
    {
        $uri = urldecode($_SERVER['REQUEST_URI']);
        $root = static::root();
        if (Str::startsWith($uri, $root)) {
            return '/' . ltrim(Str::removeStart($uri, $root), '/');
        }
        return $uri;
    }

    /**
     * Get request URI root
     */
    public static function root(): string
    {
        return '/' . ltrim(preg_replace('~[^/]+$~', '', $_SERVER['SCRIPT_NAME']), '/');
    }

    /**
     * Get request protocol
     */
    public static function protocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    /**
     * Get visitor IP
     *
     * @param bool $strict Whether to ignore X-Forwarded-For header or not
     */
    public static function ip(bool $strict = false): string
    {
        if (!$strict && getenv('HTTP_X_FORWARDED_FOR')) {
            return getenv('HTTP_X_FORWARDED_FOR');
        }
        return getenv('REMOTE_ADDR');
    }

    /**
     * Return request content length in bytes
     */
    public static function contentLength(): ?int
    {
        return isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : null;
    }

    /**
     * Get request referer
     */
    public static function referer(): ?string
    {
        return static::hasHeader('Referer') ? static::$headers['Referer'] : null;
    }

    /**
     * Check if the request referer has the same origin
     *
     * @param string $path Optional URI path
     */
    public static function validateReferer(?string $path = null): bool
    {
        $base = Uri::normalize(Uri::base() . '/' . ltrim($path, '/'));
        return Str::startsWith((string) static::referer(), $base);
    }

    /**
     * Get request origin
     */
    public static function origin(): ?string
    {
        return static::hasHeader('Origin') ? static::$headers['Origin'] : null;
    }

    /**
     * Get request user agent
     */
    public static function userAgent(): ?string
    {
        return static::hasHeader('User-Agent') ? static::$headers['User-Agent'] : null;
    }

    /**
     * Get request raw GET or POST data
     */
    public static function rawData(): ?string
    {
        if (static::method() === 'GET') {
            return parse_url(static::uri(), PHP_URL_QUERY);
        }
        return file_get_contents('php://input');
    }

    /**
     * Return whether request is secure or not
     */
    public static function isHTTPS(): bool
    {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        return false;
    }

    /**
     * Return whether request is a XmlHttpRequest (AJAX)
     */
    public static function isXHR(): bool
    {
        return static::hasHeader('X-Requested-With') && strtolower(static::$headers['X-Requested-With']) === 'xmlhttprequest';
    }

    /**
     * Return whether a request comes from localhost
     */
    public static function isLocalhost(): bool
    {
        return in_array(static::ip(true), self::LOCALHOST_IP_ADDRESSES, true);
    }

    /**
     * Return whether request has GET data
     */
    public static function hasGetData(): bool
    {
        return !empty($_GET);
    }

    /**
     * Return whether request has POST data
     */
    public static function hasPostData(): bool
    {
        return !empty($_POST);
    }

    /**
     * Return whether request has GET or POST data
     */
    public static function hasData(): bool
    {
        return static::hasGetData() || static::hasPostData();
    }

    /**
     * Return an array containing GET data
     */
    public static function getData(): array
    {
        return $_GET;
    }

    /**
     * Return an array containing POST data
     */
    public static function postData(): array
    {
        return $_POST;
    }

    /**
     * Return whether request has files
     */
    public static function hasFiles(): bool
    {
        if (empty($_FILES)) {
            return false;
        }
        foreach (static::files() as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return an array containing request incoming files
     */
    public static function files(): array
    {
        if (!empty(static::$files)) {
            return static::$files;
        }
        if (!is_array($_FILES)) {
            return static::$files;
        }
        foreach ($_FILES as $data) {
            if (!is_array($data)) {
                return static::$files;
            }
            foreach ($data as $param => $value) {
                if (is_array($value)) {
                    foreach ($value as $index => $v) {
                        static::$files[$index][$param] = $v;
                    }
                } else {
                    static::$files[0][$param] = $value;
                }
            }
        }
        return static::$files;
    }

    /**
     * Return whether request as a given header
     */
    public static function hasHeader(string $header): bool
    {
        return isset(static::headers()[$header]);
    }

    /**
     * Return an array containing request headers
     */
    public static function headers(): array
    {
        if (!empty(static::$headers)) {
            return static::$headers;
        }
        foreach ($_SERVER as $key => $value) {
            if (Str::startsWith($key, 'HTTP_')) {
                $key = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                static::$headers[$key] = $value;
            }
        }
        return static::$headers;
    }
}
