<?php

namespace Formwork\Utils;

class Arr
{
    /**
     * Get data by key returning a default value if key is not present in a given array,
     * using dot notation to traverse if literal key is not found
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }

    /**
     * Return whether a key is present in a given array, using dot notation to traverse
     * if literal key is not found
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }
        return true;
    }

    /**
     * Recursively append elements from the second array that are missing in the first
     */
    public static function appendMissing(array $array1, array $array2): array
    {
        foreach ($array1 as $key => $value) {
            if (is_array($value) && array_key_exists($key, $array2) && is_array($array2[$key])) {
                $array1[$key] = static::appendMissing($array1[$key], $array2[$key]);
            }
        }
        return $array1 + $array2;
    }

    /**
     * Return a random value from a given array
     */
    public static function random(array $array, $default = null)
    {
        return count($array) > 0 ? $array[array_rand($array)] : $default;
    }

    /**
     * Return a given array with its values shuffled optionally preserving the key/value pairs
     */
    public static function shuffle(array $array, bool $preserveKeys = false): array
    {
        if (count($array) <= 1) {
            return $array;
        }
        if (!$preserveKeys) {
            shuffle($array);
            return $array;
        }
        $keys = array_keys($array);
        shuffle($keys);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $array[$key];
        }
        return $result;
    }
}
