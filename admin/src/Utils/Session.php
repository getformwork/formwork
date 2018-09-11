<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\HTTPRequest;

class Session
{
    public static function start()
    {
        $options = array(
            'name' => 'formwork_session',
            'cookie_path' => HTTPRequest::root(),
            'cookie_httponly' => true,
            'use_strict_mode' => true
        );
        if (session_status() === PHP_SESSION_NONE) {
            session_start($options);
        }
    }

    public static function set($key, $value)
    {
        static::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
        static::start();
        if (static::has($key)) {
            return $_SESSION[$key];
        }
    }

    public static function has($key)
    {
        static::start();
        return isset($_SESSION[$key]);
    }

    public static function remove($key)
    {
        static::start();
        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy()
    {
        session_destroy();
    }
}
