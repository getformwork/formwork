<?php

namespace Formwork\Users\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;
use SensitiveParameter;

final class Password
{
    use StaticClass;

    private const int BCRYPT_MAX_LENGTH = 72;

    /**
     * Hash a password
     *
     * @throws InvalidArgumentException if the password exceeds the maximum supported length
     */
    public static function hash(
        #[SensitiveParameter]
        string $password
    ): string {
        // The `PASSWORD_BCRYPT` algorithm has a maximum password length of 72 characters
        // @see https://www.php.net/manual/en/function.password-hash.php

        // @phpstan-ignore identical.alwaysTrue
        if (PASSWORD_DEFAULT === PASSWORD_BCRYPT && strlen($password) > self::BCRYPT_MAX_LENGTH) {
            throw new InvalidArgumentException('Password exceeds the maximum supported length');
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify if given password matches an hash
     */
    public static function verify(
        #[SensitiveParameter]
        string $password,
        #[SensitiveParameter]
        string $hash
    ): bool {
        return password_verify($password, $hash);
    }
}
