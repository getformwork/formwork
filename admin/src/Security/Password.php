<?php

namespace Formwork\Admin\Security;

class Password
{
    public static function hash($password)
    {
        if (!static::legacy()) {
            return password_hash($password, PASSWORD_DEFAULT);
        }

        // Unsecure salt generation for legacy PHP version (< 5.5.0)
        $length = 22;
        $charset = implode(array_merge(array('.', '/'), range(0, 9), range('A', 'Z'), range('a', 'z')));
        $salt = '$2a$11$';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $charset[mt_rand(0, strlen($charset) - 1)];
        }
        return crypt($password, $salt);
    }

    public function verify($password, $hash)
    {
        if (!static::legacy()) {
            return password_verify($password, $hash);
        }

        // Legacy PHP (< 5.5.0) validation with crypt
        return crypt($password, $hash) === $hash;
    }

    private static function legacy()
    {
        return !function_exists('password_hash') || !function_exists('password_verify');
    }
}
