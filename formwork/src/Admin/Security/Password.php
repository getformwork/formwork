<?php

namespace Formwork\Admin\Security;

use Formwork\Traits\StaticClass;

class Password
{
    use StaticClass;

    /**
     * Hash a password
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify if given password matches an hash
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
