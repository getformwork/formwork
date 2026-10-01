<?php

namespace Formwork\Tests;

class Environment
{
    private static array $extensions = [
        'dom'      => true,
        'fileinfo' => true,
        'gd'       => true,
        'mbstring' => true,
        'openssl'  => true,
        'zip'      => true,
    ];

    public static function enableExtension(string $extension): void
    {
        self::$extensions[$extension] = true;
    }

    public static function disableExtension(string $extension): void
    {
        self::$extensions[$extension] = false;
    }

    public static function isExtensionEnabled(string $extension): bool
    {
        return self::$extensions[$extension] ?? false;
    }
}
