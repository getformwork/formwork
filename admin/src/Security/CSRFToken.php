<?php

namespace Formwork\Admin\Security;

use Formwork\Admin\Utils\Session;
use Formwork\Utils\HTTPRequest;
use RuntimeException;

class CSRFToken
{
    /**
     * Current CSRF token
     */
    protected static $token;

    /**
     * Generate a new CSRF token
     *
     * @return string
     */
    public static function generate()
    {
        static::$token = function_exists('random_bytes') ? bin2hex(random_bytes(20)) : sha1(microtime());
        Session::set('CSRF_TOKEN', static::$token);
        return static::$token;
    }

    /**
     * Get current CSRF token
     *
     * @return string
     */
    public static function get()
    {
        return Session::has('CSRF_TOKEN') ? Session::get('CSRF_TOKEN') : null;
    }

    /**
     * Check if given CSRF token is valid
     *
     * @param string $token
     *
     * @return bool
     */
    public static function validate($token = null)
    {
        if (is_null($token)) {
            $postData = HTTPRequest::postData();
            $valid = isset($postData['csrf-token']) && $postData['csrf-token'] === static::get();
        } else {
            $valid = $token === static::get();
        }
        if (!$valid) {
            static::destroy();
            throw new RuntimeException('CSRF token not valid');
        }
        return $valid;
    }

    /**
     * Remove CSRF token from session data
     */
    public static function destroy()
    {
        Session::remove('CSRF_TOKEN');
    }
}
