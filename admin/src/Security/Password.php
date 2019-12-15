<?php

namespace Formwork\Admin\Security;

class Password
{
    /**
     * Hash a password
     *
     * @return string
     */
    public static function hash(string $password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify if given password matches an hash
     *
     * @return bool
     */
    public static function verify(string $password, string $hash)
    {
        return password_verify($password, $hash);
    }
}
