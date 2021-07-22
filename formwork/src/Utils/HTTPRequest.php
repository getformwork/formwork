<?php

namespace Formwork\Utils;

use Formwork\Data\Collection;
use Formwork\Data\DataGetter;

class HTTPRequest
{
    /**
     * Localhost IP addresses
     */
    protected const LOCALHOST_IP_ADDRESSES = ['127.0.0.1', '::1'];

    /**
     * DataGetter containing HTTP GET data
     */
    protected static DataGetter $getData;

    /**
     * DataGetter containing HTTP POST data
     */
    protected static DataGetter $postData;

    /**
     * DataGetter containing HTTP Cookies
     */
    protected static DataGetter $cookies;

    /**
     * DataGetter containing HTTP headers
     */
    protected static DataGetter $headers;

    /**
     * Collection containing HTTP request files
     */
    protected static Collection $files;

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
        return static::headers()->get('Referer');
    }

    /**
     * Check if the request referer has the same origin
     *
     * @param string $path Optional URI path
     */
    public static function validateReferer(string $path = null): bool
    {
        $base = Uri::normalize(Uri::base() . '/' . ltrim($path, '/'));
        return Str::startsWith((string) static::referer(), $base);
    }

    /**
     * Get request origin
     */
    public static function origin(): ?string
    {
        return static::headers()->get('Origin');
    }

    /**
     * Get request user agent
     */
    public static function userAgent(): ?string
    {
        return static::headers()->get('User-Agent');
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
        return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Return whether request is a XmlHttpRequest (AJAX)
     */
    public static function isXHR(): bool
    {
        return strtolower(static::headers()->get('X-Requested-With')) === 'xmlhttprequest';
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
    public static function getData(): DataGetter
    {
        if (isset(static::$getData)) {
            return static::$getData;
        }
        return static::$getData = new DataGetter($_GET);
    }

    /**
     * Return an array containing POST data
     */
    public static function postData(): DataGetter
    {
        if (isset(static::$postData)) {
            return static::$postData;
        }
        return static::$postData = new DataGetter($_POST);
    }

    /**
     * Return a DataGetter containing cookies
     */
    public static function cookies(): DataGetter
    {
        if (isset(static::$cookies)) {
            return static::$cookies;
        }
        return static::$cookies = new DataGetter($_COOKIE);
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
    public static function files(): Collection
    {
        if (isset(static::$files)) {
            return static::$files;
        }
        $files = [];
        if (is_array($_FILES)) {
            foreach ($_FILES as $data) {
                if (!is_array($data)) {
                    break;
                }
                foreach ($data as $param => $value) {
                    if (is_array($value)) {
                        foreach ($value as $index => $v) {
                            $files[$index][$param] = $v;
                        }
                    } else {
                        $files[0][$param] = $value;
                    }
                }
            }
        }
        return static::$files = new Collection($files);
    }

    /**
     * Return an array containing request headers
     */
    public static function headers(): DataGetter
    {
        if (isset(static::$headers)) {
            return static::$headers;
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (Str::startsWith($key, 'HTTP_')) {
                $key = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$key] = $value;
            }
        }
        return static::$headers = new DataGetter($headers);
    }
}
