<?php

namespace Formwork\Tests\Utils\Fixtures;

final class FileSystemFixture
{
    private static array $enabledFunctions = [
        'cwd'               => true,
        'fileatime'         => true,
        'filectime'         => true,
        'filemtime'         => true,
        'touch'             => true,
        'fileperms'         => true,
        'filesize'          => true,
        'unlink'            => true,
        'rmdir'             => true,
        'copy'              => true,
        'symlink'           => true,
        'readlink'          => true,
        'rename'            => true,
        'file_get_contents' => true,
        'file_put_contents' => true,
        'opendir'           => true,
        'fopen'             => true,
        'mkdir'             => true,
    ];

    public static function enable(string $function): void
    {
        self::$enabledFunctions[$function] = true;
    }

    public static function disable(string $function): void
    {
        self::$enabledFunctions[$function] = false;
    }

    public static function enableAll(): void
    {
        foreach (array_keys(self::$enabledFunctions) as $function) {
            self::enable($function);
        }
    }

    public static function disableAll(): void
    {
        foreach (array_keys(self::$enabledFunctions) as $function) {
            self::disable($function);
        }
    }

    public static function cwd(): string|false
    {
        if (self::$enabledFunctions['cwd']) {
            return getcwd();
        }
        return false;
    }

    public static function fileatime(string $filename): int|false
    {
        if (self::$enabledFunctions['fileatime']) {
            return fileatime($filename);
        }
        return false;
    }

    public static function filectime(string $filename): int|false
    {
        if (self::$enabledFunctions['filectime']) {
            return filectime($filename);
        }
        return false;
    }

    public static function filemtime(string $filename): int|false
    {
        if (self::$enabledFunctions['filemtime']) {
            return filemtime($filename);
        }
        return false;
    }

    public static function touch(string $filename, ?int $time = null, ?int $atime = null): bool
    {
        if (!self::$enabledFunctions['touch']) {
            return false;
        }
        return touch($filename, $time, $atime);
    }

    public static function fileperms(string $filename): int|false
    {
        if (self::$enabledFunctions['fileperms']) {
            return fileperms($filename);
        }
        return false;
    }

    public static function filesize(string $filename): int|false
    {
        if (self::$enabledFunctions['filesize']) {
            return filesize($filename);
        }
        return false;
    }

    public static function unlink(string $filename): bool
    {
        if (!self::$enabledFunctions['unlink']) {
            return false;
        }
        return unlink($filename);
    }

    public static function rmdir(string $dirname): bool
    {
        if (!self::$enabledFunctions['rmdir']) {
            return false;
        }
        return rmdir($dirname);
    }

    public static function copy(string $from, string $to): bool
    {
        if (!self::$enabledFunctions['copy']) {
            return false;
        }
        return copy($from, $to);
    }

    public static function symlink(string $target, string $link): bool
    {
        if (!self::$enabledFunctions['symlink']) {
            return false;
        }
        return symlink($target, $link);
    }

    public static function readlink(string $path): string|false
    {
        if (!self::$enabledFunctions['readlink']) {
            return false;
        }
        return readlink($path);
    }

    public static function rename(string $from, string $to): bool
    {
        if (!self::$enabledFunctions['rename']) {
            return false;
        }
        return rename($from, $to);
    }

    public static function file_get_contents(string $filename): string|false
    {
        if (!self::$enabledFunctions['file_get_contents']) {
            return false;
        }
        return file_get_contents($filename);
    }

    public static function file_put_contents(string $filename, mixed $data, int $flags = 0): int|false
    {
        if (!self::$enabledFunctions['file_put_contents']) {
            return false;
        }
        return file_put_contents($filename, $data, $flags);
    }

    public static function opendir(string $directory): mixed
    {
        if (!self::$enabledFunctions['opendir']) {
            return false;
        }
        return opendir($directory);
    }

    public static function fopen(string $filename, string $mode): mixed
    {
        if (!self::$enabledFunctions['fopen']) {
            return false;
        }
        return fopen($filename, $mode);
    }

    public static function mkdir(string $directory, int $mode = 0o777, bool $recursive = false): bool
    {
        if (!self::$enabledFunctions['mkdir']) {
            return false;
        }
        return mkdir($directory, $mode, $recursive);
    }
}
