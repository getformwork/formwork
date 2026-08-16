<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

final class Path
{
    use StaticClass;

    /**
     * Default path separator (forward slash)
     */
    private const string DEFAULT_SEPARATOR = '/';

    /**
     * Regex matching multiple separators (forward and backward slash) to split paths into segments
     */
    private const string SEPARATORS_REGEX = '~[/\\\]+~';

    /**
     * Return whether a path is absolute
     */
    public static function isAbsolute(string $path): bool
    {
        return $path !== '' && ($path[0] === '/' || $path[0] === '\\' || (strlen($path) >= 2 && ctype_alpha($path[0]) && $path[1] === ':'));
    }

    /**
     * Return whether a directory separator is valid
     */
    public static function isSeparator(string $separator): bool
    {
        return $separator === '/' || $separator === '\\';
    }

    /**
     * Return whether an absolute path is relative to a given absolute base
     *
     * @throws InvalidArgumentException If the path parameter is not an absolute path
     * @throws InvalidArgumentException If the base parameter is not an absolute path
     */
    public static function isRelativeTo(string $path, string $base): bool
    {
        if (!self::isAbsolute($path)) {
            throw new InvalidArgumentException('$path must be an absolute path');
        }
        if (!self::isAbsolute($base)) {
            throw new InvalidArgumentException('$base must be an absolute path');
        }
        $normalizedPath = self::normalize($path);
        $normalizedBase = self::normalize($base);
        return $normalizedPath === $normalizedBase || Str::startsWith(
            $normalizedPath,
            Str::append($normalizedBase, self::DEFAULT_SEPARATOR)
        );
    }

    /**
     * Normalize path separators and remove `.` and `..` segments
     *
     * @throws InvalidArgumentException If the separator is not a valid directory separator
     */
    public static function normalize(string $path, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!self::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        return self::dropDriveLetter($path) . implode($separator, self::split($path));
    }

    /**
     * Split a path into segments removing `.` and `..`
     *
     * @return array<string>
     */
    public static function split(string $path): array
    {
        $result = [];
        if (($segments = preg_split(self::SEPARATORS_REGEX, $path))) {
            foreach ($segments as $segment) {
                if ($segment === '..' && end($result) !== '..' && $result !== []) {
                    if (end($result) !== '') {
                        array_pop($result);
                    }
                } elseif ($segment !== '.') {
                    $result[] = $segment;
                }
            }
        }
        return $result;
    }

    /**
     * Join together an array of paths
     *
     * @param array<string> $paths
     *
     * @throws InvalidArgumentException If the separator is not a valid directory separator
     */
    public static function join(array $paths, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!self::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        return self::normalize(implode($separator, $paths), $separator);
    }

    /**
     * Resolve a path against a given base
     *
     * @throws InvalidArgumentException If the separator is not a valid directory separator
     */
    public static function resolve(string $path, string $base, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!self::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        $pathDriveLetter = self::dropDriveLetter($path);
        $baseDriveLetter = self::dropDriveLetter($base);
        if (self::isAbsolute($path)) {
            if ($pathDriveLetter === '') {
                $pathDriveLetter = $baseDriveLetter;
            }
            return $pathDriveLetter . self::normalize($path, $separator);
        }
        return $baseDriveLetter . self::join([$base, $path], $separator);
    }

    /**
     * Make an absolute path relative to a given base
     *
     * @throws InvalidArgumentException If the path parameter is not an absolute path
     * @throws InvalidArgumentException If the base parameter is not an absolute path
     * @throws InvalidArgumentException If the separator is not a valid directory separator
     * @throws InvalidArgumentException If the path and base have incompatible drive letters
     */
    public static function makeRelative(string $path, string $base, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!self::isAbsolute($path)) {
            throw new InvalidArgumentException('$path must be an absolute path');
        }
        if (!self::isAbsolute($base)) {
            throw new InvalidArgumentException('$base must be an absolute path');
        }
        if (!self::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        $pathDriveLetter = self::dropDriveLetter($path);
        $baseDriveLetter = self::dropDriveLetter($base);
        if ($pathDriveLetter !== '' && $baseDriveLetter !== '' && strcasecmp($pathDriveLetter, $baseDriveLetter) !== 0) {
            throw new InvalidArgumentException('$path and $base must have a compatible drive letter');
        }
        $pathSegments = self::split($path);
        $baseSegments = self::split($base);
        if (end($baseSegments) === '') {
            array_pop($baseSegments);
        }
        $i = 0;
        while (isset($pathSegments[$i], $baseSegments[$i]) && $pathSegments[$i] === $baseSegments[$i]) {
            $i++;
        }
        return str_repeat('..' . $separator, count($baseSegments) - $i) . implode($separator, array_slice($pathSegments, $i));
    }

    /**
     * Return drive letter from $path after removing it
     */
    private static function dropDriveLetter(string &$path): string
    {
        $letter = '';
        if (strlen($path) >= 2 && ctype_alpha($path[0]) && $path[1] === ':') {
            $letter = substr($path, 0, 2);
            $path = substr($path, 2);
        }
        return $letter;
    }
}
