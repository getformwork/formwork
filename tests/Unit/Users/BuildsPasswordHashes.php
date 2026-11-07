<?php

namespace Formwork\Tests\Unit\Users;

/**
 * Builds password hashes with the lowest cost, as the cost is irrelevant to the tests
 */
trait BuildsPasswordHashes
{
    /**
     * @var array<string, string>
     */
    private static array $passwordHashes = [];

    protected static function hashPassword(string $password): string
    {
        return self::$passwordHashes[$password] ??= password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
    }
}
