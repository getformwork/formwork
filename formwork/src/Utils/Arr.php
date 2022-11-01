<?php

namespace Formwork\Utils;

use UnexpectedValueException;

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
     * Set data by key using dot notation to traverse if literal key is not found
     */
    public static function set(array &$array, string $key, $value): void
    {
        if (array_key_exists($key, $array)) {
            $array[$key] = $value;
            return;
        }
        $segments = explode('.', $key);
        $key = array_pop($segments);
        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $array)) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }
        $array[$key] = $value;
    }

    /**
     * Remove data by key using dot notation to traverse if literal key is not found
     */
    public static function remove(array &$array, string $key): void
    {
        if (array_key_exists($key, $array)) {
            unset($array[$key]);
            return;
        }
        $segments = explode('.', $key);
        $key = array_pop($segments);
        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $array)) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }
        unset($array[$key]);
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

    /**
     * Sort array elements with options
     * `array_multisort()` is used internally to do the sorting.
     *
     * @see https://www.php.net/manual/en/function.array-multisort.php
     *
     * @param array $options An array of sort options:
     *                       - `direction`: sets the direction used to sort the given array,
     *                       possible values are `SORT_ASC` and `SORT_DESC`. Default: `SORT_ASC`.
     *                       - `type`: sets the type of the sorting, possible values are `SORT_REGULAR`,
     *                       `SORT_NUMERIC`, `SORT_STRING` and `SORT_NATURAL`. Default: `SORT_NATURAL`.
     *                       - `caseSensitive` (bool): whether to perform a case-sensitive sorting.
     *                       Default: `false`.
     *                       - `sortBy` (?array): a second optional array on which the sorting
     *                       will be based. The elements of the first array will be ordered based on
     *                       the result of sorting `sortBy` with the other options given. Default: `null`.
     */
    public static function sort(array $array, array $options = []): array
    {
        $options += [
            'direction'     => SORT_ASC,
            'type'          => SORT_NATURAL,
            'caseSensitive' => false,
            'sortBy'        => null
        ];

        if (!in_array($options['direction'], [SORT_ASC, SORT_DESC], true)) {
            throw new UnexpectedValueException(sprintf('%s() only accepts SORT_ASC and SORT_DESC as "direction" option', __METHOD__));
        }

        if (!in_array($options['type'], [SORT_REGULAR, SORT_NUMERIC, SORT_STRING, SORT_NATURAL], true)) {
            throw new UnexpectedValueException(sprintf('%s() only accepts SORT_REGULAR, SORT_NUMERIC, SORT_STRING and SORT_NATURAL as "type" option', __METHOD__));
        }

        $flags = $options['type'];

        if (!is_bool($options['caseSensitive'])) {
            throw new UnexpectedValueException(sprintf('%s() only accepts booleans as "caseSensitive" option', __METHOD__));
        }

        if ($options['caseSensitive'] === false) {
            $flags |= SORT_FLAG_CASE;
        }

        if ($options['sortBy'] === null) {
            array_multisort($array, $options['direction'], $flags);
        } elseif (is_array($options['sortBy'])) {
            array_multisort($options['sortBy'], $options['direction'], $flags, $array);
        } else {
            throw new UnexpectedValueException(sprintf('%s() only accepts arrays or "null" as "sortBy" option', __METHOD__));
        }

        return $array;
    }

    /**
     * Return whether the given array is not empty and its keys are not sequential
     */
    public static function isAssociative(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }
}
