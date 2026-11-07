<?php

namespace Formwork\Tests\Unit\Authentication\Fixtures;

final class NativeFunctionsFixture
{
    /**
     * @var array<string, bool>
     */
    private static array $enabledFunctions = [
        'usleep' => true,
    ];

    public static function enable(string $function): void
    {
        self::$enabledFunctions[$function] = true;
    }

    public static function disable(string $function): void
    {
        self::$enabledFunctions[$function] = false;
    }

    public static function usleep(int $microseconds): void
    {
        if (self::$enabledFunctions['usleep']) {
            usleep($microseconds);
        }
    }
}
