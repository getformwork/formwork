<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Cookie;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', true);
            $options = array(
                'path' => HTTPRequest::root(),
                'secure' => HTTPRequest::isHTTPS(),
                'httponly' => true,
                'samesite' => 'strict'
            );
            session_name('formwork_session');
            session_start();
            Cookie::send(session_name(), session_id(), $options, true);
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
