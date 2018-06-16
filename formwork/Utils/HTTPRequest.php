<?php

namespace Formwork\Utils;

class HTTPRequest {

    protected static $headers = array();

    protected static $files = array();

    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function uri() {
        $uri = $_SERVER['REQUEST_URI'];
        $root = static::root();
        if (strpos($uri, $root) === 0) {
            return '/' . ltrim(substr($uri, strlen($root)), '/');
        }
        return $uri;
    }

    public static function root() {
        return '/' . ltrim(preg_replace('~[^/]+$~', '', $_SERVER['SCRIPT_NAME']), '/');
    }

    public static function protocol() {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    public static function ip($strict = false) {
        if (!$strict && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function referer() {
        return isset(static::headers()['Referer']) ? static::headers()['Referer'] : null;
    }

    public static function origin() {
        return isset(static::headers()['Origin']) ? static::headers()['Origin'] : null;
    }

    public static function userAgent() {
        return isset(static::headers()['User-Agent']) ? static::headers()['User-Agent'] : null;
    }

    public static function rawData() {
        if (static::method() === 'GET') return parse_url(static::uri(), PHP_URL_QUERY);
        return file_get_contents('php://input');
    }

    public static function isXHR() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    public static function hasGetData() {
        return !empty($_GET);
    }

    public static function hasPostData() {
        return !empty($_POST);
    }

    public static function hasData() {
        return static::hasGetData() || static::hasPostData();
    }

    public static function getData() {
        return $_GET;
    }

    public static function postData() {
        return $_POST;
    }

    public static function postDataFromRaw() {
        $data = array();
        foreach (explode('&', static::rawData()) as $pair) {
            list($key, $value) = explode('=', $pair);
            $data[urldecode($key)] = urldecode($value);
        }
        return $data;
    }

    public static function hasFiles() {
        if (empty($_FILES)) return false;
        foreach (static::files() as $file) {
            if ($file['error'] === 4) return false;
        }
        return true;
    }

    public static function files() {
        if(!empty(static::$files)) return static::$files;
        if (!is_array($_FILES)) return static::$files;
        foreach ($_FILES as $field => $data) {
            if (!is_array($data)) return static::$files;
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

    public static function headers() {
      if(!empty(static::$headers)) return static::$headers;
      foreach ($_SERVER as $key => $value) {
          if (strpos($key, 'HTTP_') === 0) {
              $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
              static::$headers[$key] = $value;
          }
      }
      return static::$headers;
    }

}
