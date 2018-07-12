<?php

namespace Formwork\Admin\Security;

use Formwork\Admin\Utils\Session;
use Exception;

class CSRFToken
{
    protected static $token;

    public static function generate()
    {
        static::$token = function_exists('random_bytes') ? bin2hex(random_bytes(20)) : sha1(microtime());
        Session::set('CSRF_TOKEN', static::$token);
        return static::$token;
    }

    public static function get()
    {
        return Session::has('CSRF_TOKEN') ? Session::get('CSRF_TOKEN') : null;
    }

    public static function validate($token = null)
    {
        if (is_null($token)) {
            $valid = isset($_POST['csrf-token']) && $_POST['csrf-token'] === static::get();
        } else {
            $valid = $token === static::get();
        }
        if (!$valid) {
            static::destroy();
            throw new Exception('CSRF token not valid');
        }
        return $valid;
    }

    public static function destroy()
    {
        Session::remove('CSRF_TOKEN');
    }
}
