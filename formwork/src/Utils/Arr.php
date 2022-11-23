<?php

namespace Formwork\Utils;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Traits\StaticClass;
use Traversable;
use UnexpectedValueException;

class Arr
{
    use StaticClass;

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
     * Remove from an array all the occurrences of the given value
     */
    public static function pull(array &$array, $value): void
    {
        foreach ($array as $key => $v) {
            if ($v === $value) {
                unset($array[$key]);
            }
        }
    }

    /**
     * Get the array value at the given index,
     * negative indices are not allowed, use `Arr:at()` instead
     */
    public static function nth(array $array, int $index)
    {
        return array_values($array)[$index] ?? null;
    }

    /**
     * Get the array value at the given index,
     * negative indices are allowed and start from the end
     */
    public static function at(array $array, int $index)
    {
        return array_values($array)[$index >= 0 ? $index : count($array) + $index] ?? null;
    }

    /**
     * Get the index of the given value or null if not found
     */
    public static function indexOf(array $array, $value): ?int
    {
        $index = array_search($value, array_values($array), true);
        return $index !== false ? $index : null;
    }

    /**
     * Get the key of the given value or null if not found
     */
    public static function keyOf(array $array, $value): int|string|null
    {
        $key = array_search($value, $array, true);
        return $key !== false ? $key : null;
    }

    /**
     * Return the duplicate elements of the array
     */
    public static function duplicates(array $array): array
    {
        return array_diff_key($array, array_unique($array));
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
     * Return whether the given array is not empty and its keys are not sequential
     */
    public static function isAssociative(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Apply a callback to the given array and return the result
     *
     * The key of each element is passed to the callback as second argument
     */
    public static function map(array $array, callable $callback): array
    {
        $keys = array_keys($array);
        return array_combine($keys, array_map($callback, $array, $keys));
    }

    /**
     * Apply a callback to the given array keys and return the result
     *
     * The value of each element is passed to the callback as second argument
     */
    public static function mapKeys(array $array, callable $callback): array
    {
        $keys = array_keys($array);
        return array_combine(array_map($callback, $keys, $array), $array);
    }

    /**
     * Filter an array keeping only the values for which the callback returns `true`
     *
     * The key of each element is passed to the callback as second argument
     */
    public static function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Reject values from an array keeping only the values for which the callback returns `false`
     *
     * The key of each element is passed to the callback as second argument
     */
    public static function reject(array $array, callable $callback): array
    {
        return static::filter($array, fn ($value, $key) => !$callback($value, $key));
    }

    /**
     * Return whether every element of the array passes a test callback
     *
     * The key of each element is passed to the callback as second argument
     */
    public static function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return whether some element of the array passes a test callback
     *
     * The key of each element is passed to the callback as second argument
     */
    public static function some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the value corresponding to the specified key from each element of an array
     */
    public static function pluck(array $array, string $key, $default = null): array
    {
        $result = [];

        foreach ($array as $k => $value) {
            $result[$k] = static::get(static::from($value), $key, $default);
        }

        return $result;
    }

    /**
     * Sort an array with the given options
     *
     * @param $direction     Direction of sorting. Possible values are `SORT_ASC` and `SORT_DESC`.
     * @param $type          Type of sorting. Possible values are `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING` and `SORT_NATURAL`.
     * @param $caseSensitive Whether to perform a case-sensitive sorting
     * @param $sortBy        A callback or second array of values used to sort the first
     * @param $preserveKeys Whether to preserve array keys after sorting
     */
    public static function sort(
        array $array,
        int $direction = SORT_ASC,
        int $type = SORT_NATURAL,
        array|callable $sortBy = null,
        bool $caseSensitive = false,
        bool $preserveKeys = true
    ): array {
        if (!in_array($direction, [SORT_ASC, SORT_DESC], true)) {
            throw new UnexpectedValueException(sprintf('%s() only accepts SORT_ASC and SORT_DESC as "direction" option', __METHOD__));
        }

        if (!in_array($type, [SORT_REGULAR, SORT_NUMERIC, SORT_STRING, SORT_NATURAL], true)) {
            throw new UnexpectedValueException(sprintf('%s() only accepts SORT_REGULAR, SORT_NUMERIC, SORT_STRING and SORT_NATURAL as "type" option', __METHOD__));
        }

        $flags = $type;

        if ($caseSensitive === false) {
            $flags |= SORT_FLAG_CASE;
        }

        if (is_callable($sortBy)) {
            $function = $preserveKeys ? 'uasort' : 'usort';
            $function($array, $sortBy);
        } else {
            $keys = $preserveKeys ? array_keys($array) : [];

            $arguments = [];

            if ($sortBy === null) {
                $arguments = [&$array, $direction, $flags];
            } elseif (is_array($sortBy)) {
                $arguments = [&$sortBy, $direction, $flags, &$array];
            }

            if ($preserveKeys) {
                $arguments[] = &$keys;
            }

            array_multisort(...$arguments);

            if ($preserveKeys) {
                $array = array_combine($keys, $array);
            }
        }

        return $array;
    }

    /**
     * Try to convert the given object to array
     */
    public static function from($object): array
    {
        switch (true) {
            case is_array($object):
                return $object;

            case $object instanceof Arrayable:
                return $object->toArray();

            case $object instanceof Traversable:
                return iterator_to_array($object);
        }

        throw new UnexpectedValueException(sprintf('Cannot convert to array an object of type %s', get_debug_type($object)));
    }
}
