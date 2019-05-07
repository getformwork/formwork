<?php

namespace Formwork\Utils;

class HTTPRequest
{
    /**
     * Array containing HTTP request headers
     *
     * @var array
     */
    protected static $headers = array();

    /**
     * Array containing HTTP request files
     *
     * @var array
     */
    protected static $files = array();

    /**
     * Get request method
     *
     * @return string
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get request type (HTTP or XHR)
     *
     * @return string
     */
    public static function type()
    {
        return static::isXHR() ? 'XHR' : 'HTTP';
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public static function uri()
    {
        $uri = urldecode($_SERVER['REQUEST_URI']);
        $root = static::root();
        if (Str::startsWith($uri, $root)) {
            return '/' . ltrim(substr($uri, strlen($root)), '/');
        }
        return $uri;
    }

    /**
     * Get request URI root
     *
     * @return string
     */
    public static function root()
    {
        return '/' . ltrim(preg_replace('~[^/]+$~', '', $_SERVER['SCRIPT_NAME']), '/');
    }

    /**
     * Get request protocol
     *
     * @return string
     */
    public static function protocol()
    {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    /**
     * Get visitor IP
     *
     * @param bool $strict Whether to ignore X-Forwarded-For header or not
     *
     * @return string
     */
    public static function ip($strict = false)
    {
        if (!$strict && static::hasHeader('X-Forwarded-For')) {
            return static::$headers['X-Forwarded-For'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Return request content length in bytes
     *
     * @return int|null
     */
    public static function contentLength()
    {
        return isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : null;
    }

    /**
     * Get request referer
     *
     * @return string|null
     */
    public static function referer()
    {
        return static::hasHeader('Referer') ? static::$headers['Referer'] : null;
    }

    /**
     * Check if the request referer has the same origin
     *
     * @param string $path Optional URI path
     *
     * @return bool
     */
    public static function validateReferer($path = null)
    {
        $base = Uri::normalize(Uri::base() . '/' . ltrim($path, '/'));
        return Str::startsWith(static::referer(), $base);
    }

    /**
     * Get request origin
     *
     * @return string|null
     */
    public static function origin()
    {
        return static::hasHeader('Origin') ? static::$headers['Origin'] : null;
    }

    /**
     * Get request user agent
     *
     * @return string|null
     */
    public static function userAgent()
    {
        return static::hasHeader('User-Agent') ? static::$headers['User-Agent'] : null;
    }

    /**
     * Get request raw GET or POST data
     *
     * @return string|null
     */
    public static function rawData()
    {
        if (static::method() === 'GET') {
            return parse_url(static::uri(), PHP_URL_QUERY);
        }
        return file_get_contents('php://input');
    }

    /**
     * Return whether request is secure or not
     *
     * @return bool
     */
    public static function isHTTPS()
    {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        return false;
    }

    /**
     * Return whether request is a XmlHttpRequest (AJAX)
     *
     * @return bool
     */
    public static function isXHR()
    {
        return static::hasHeader('X-Requested-With') && strtolower(static::$headers['X-Requested-With']) === 'xmlhttprequest';
    }

    /**
     * Return whether request has GET data
     *
     * @return bool
     */
    public static function hasGetData()
    {
        return !empty($_GET);
    }

    /**
     * Return whether request has POST data
     *
     * @return bool
     */
    public static function hasPostData()
    {
        return !empty($_POST);
    }

    /**
     * Return whether request has GET or POST data
     *
     * @return bool
     */
    public static function hasData()
    {
        return static::hasGetData() || static::hasPostData();
    }

    /**
     * Return an array containing GET data
     *
     * @return array
     */
    public static function getData()
    {
        return $_GET;
    }

    /**
     * Return an array containing POST data
     *
     * @return array
     */
    public static function postData()
    {
        return $_POST;
    }

    /**
     * Return whether request has files
     *
     * @return bool
     */
    public static function hasFiles()
    {
        if (empty($_FILES)) {
            return false;
        }
        foreach (static::files() as $file) {
            if ($file['error'] === 4) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return an array containing request incoming files
     *
     * @return array
     */
    public static function files()
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
     *
     * @param string $header
     *
     * @return bool
     */
    public static function hasHeader($header)
    {
        return isset(static::headers()[$header]);
    }

    /**
     * Return an array containing request headers
     *
     * @return array
     */
    public static function headers()
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
