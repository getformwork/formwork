<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Cookie;

class Session
{
    /**
     * Start a new session
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', true);
            $options = array(
                'path'     => HTTPRequest::root(),
                'secure'   => HTTPRequest::isHTTPS(),
                'httponly' => true,
                'samesite' => 'strict'
            );
            session_name('formwork_session');
            session_start();
            Cookie::send(session_name(), session_id(), $options, true);
        }
    }

    /**
     * Set a session key to value
     *
     * @param string $key
     */
    public static function set($key, $value)
    {
        static::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session key
     *
     * @param string $key
     */
    public static function get($key)
    {
        static::start();
        if (static::has($key)) {
            return $_SESSION[$key];
        }
    }

    /**
     * Return whether a key is in session data
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        static::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session data by key
     *
     * @param string $key
     */
    public static function remove($key)
    {
        static::start();
        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * End a session and destroy all data
     */
    public static function destroy()
    {
        session_destroy();
    }
}
