<?php

namespace Formwork\Utils;

use Formwork\Formwork;

class Session
{
    /**
     * Session name
     *
     * @var string
     */
    protected const SESSION_NAME = 'formwork_session';

    /**
     * Start a new session
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', true);
            $options = [
                'expires'  => 0,
                'path'     => HTTPRequest::root(),
                'secure'   => HTTPRequest::isHTTPS(),
                'httponly' => true,
                'samesite' => Cookie::SAMESITE_STRICT
            ];
            if (($timeout = Formwork::instance()->config()->get('admin.session_timeout')) > 0) {
                $options['expires'] = time() + $timeout * 60;
            }
            session_name(self::SESSION_NAME);
            session_start();
            if (!isset($_COOKIE[self::SESSION_NAME]) || $options['expires'] > 0) {
                // Send session cookie if not already sent or timeout is set
                Cookie::send(self::SESSION_NAME, session_id(), $options, true);
            } elseif ($_COOKIE[self::SESSION_NAME] !== session_id()) {
                // Remove cookie if session id is not valid
                unset($_COOKIE[self::SESSION_NAME]);
                Cookie::send(self::SESSION_NAME, '', ['expires' => time() - 3600] + $options, true);
            }
        }
    }

    /**
     * Set a session key to value
     */
    public static function set(string $key, $value): void
    {
        static::start();
        Arr::set($_SESSION, $key, $value);
    }

    /**
     * Get a session key
     */
    public static function get(string $key, $default = null)
    {
        static::start();
        return Arr::get($_SESSION, $key, $default);
    }

    /**
     * Return whether a key is in session data
     */
    public static function has(string $key): bool
    {
        static::start();
        return Arr::has($_SESSION, $key);
    }

    /**
     * Remove session data by key
     */
    public static function remove(string $key): void
    {
        static::start();
        Arr::remove($_SESSION, $key);
    }

    /**
     * End a session and destroy all data
     */
    public static function destroy(): void
    {
        session_destroy();
    }
}
