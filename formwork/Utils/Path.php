<?php

namespace Formwork\Utils;

use InvalidArgumentException;

class Path
{
    /**
     * Default path separator (forward slash)
     *
     * @var string
     */
    protected const DEFAULT_SEPARATOR = '/';

    /**
     * Regex matching multiple separators (forward and backward slash) to split paths into segments
     *
     * @var string
     */
    protected const SEPARATORS_REGEX = '~[/\\\\]+~';

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
     * Normalize path separators and remove '.' and '..' segments
     */
    public static function normalize(string $path, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!static::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        return static::dropDriveLetter($path) . implode($separator, static::split($path));
    }

    /**
     * Split a path into segments removing '.' and '..' ones
     */
    public static function split(string $path): array
    {
        $result = [];
        foreach (preg_split(self::SEPARATORS_REGEX, $path) as $segment) {
            if ($segment === '..' && end($result) !== '..' && !empty($result)) {
                if (end($result) !== '') {
                    array_pop($result);
                }
            } elseif ($segment !== '.') {
                $result[] = $segment;
            }
        }
        return $result;
    }

    /**
     * Join together an array of paths
     */
    public static function join(array $paths, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!static::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        return static::normalize(implode($separator, $paths), $separator);
    }

    /**
     * Resolve a path against a given base
     */
    public static function resolve(string $path, string $base, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!static::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        $pathDriveLetter = static::dropDriveLetter($path);
        $baseDriveLetter = static::dropDriveLetter($base);
        if (static::isAbsolute($path)) {
            if ($pathDriveLetter === '') {
                $pathDriveLetter = $baseDriveLetter;
            }
            return $pathDriveLetter . static::normalize($path, $separator);
        }
        return $baseDriveLetter . static::join([$base, $path], $separator);
    }

    /**
     * Make an absolute path relative to a given base
     */
    public static function makeRelative(string $path, string $base, string $separator = self::DEFAULT_SEPARATOR): string
    {
        if (!static::isAbsolute($path)) {
            throw new InvalidArgumentException('$path must be an absolute path');
        }
        if (!static::isAbsolute($base)) {
            throw new InvalidArgumentException('$base must be an absolute path');
        }
        if (!static::isSeparator($separator)) {
            throw new InvalidArgumentException('$separator must be a valid directory separator');
        }
        $pathDriveLetter = static::dropDriveLetter($path);
        $baseDriveLetter = static::dropDriveLetter($base);
        if ($pathDriveLetter !== '' && $baseDriveLetter !== '' && strcasecmp($pathDriveLetter, $baseDriveLetter) !== 0) {
            throw new InvalidArgumentException('$path and $base must have a compatible drive letter');
        }
        $pathSegments = static::split($path);
        $baseSegments = static::split($base);
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
     *
     * @internal
     */
    protected static function dropDriveLetter(string &$path): string
    {
        $letter = '';
        if (strlen($path) >= 2 && ctype_alpha($path[0]) && $path[1] === ':') {
            $letter = substr($path, 0, 2);
            $path = substr($path, 2);
        }
        return $letter;
    }
}
