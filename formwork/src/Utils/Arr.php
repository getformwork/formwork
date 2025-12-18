<?php

namespace Formwork\Utils;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Traits\StaticClass;
use Stringable;
use Traversable;
use UnexpectedValueException;

final class Arr
{
    use StaticClass;

    /**
     * Get data by key returning a default value if key is not present in a given array,
     * using dot notation to traverse if literal key is not found
     *
     * @param array<string, TValue> $array
     * @param TValue|null           $default
     *
     * @return TValue|null
     *
     * @template TValue
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
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
     *
     * @param array<string, TValue> $array
     *
     * @template TValue
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
     *
     * @param array<string, TValue> $array
     * @param TValue                $value
     *
     * @template TValue
     */
    public static function set(array &$array, string $key, mixed $value): void
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
     *
     * @param array<mixed> $array
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
            if (!array_key_exists($segment, $array) || !is_array($array[$segment])) {
                return;
            }
            $array = &$array[$segment];
        }
        unset($array[$key]);
    }

    /**
     * Convert a multidimensional array to a dot notation array, skipping non-associative arrays
     *
     * @param array<array-key, TValue> $array
     *
     * @return array<string, TValue>
     *
     * @template TValue
     */
    public static function dot(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== [] && self::isAssociative($value)) {
                foreach (self::dot($value) as $subKey => $subValue) {
                    $result[$key . '.' . $subKey] = $subValue;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert a dot notation array to a multidimensional array
     *
     * @param array<string, TValue> $array
     *
     * @return array<array-key, TValue>
     *
     * @template TValue
     */
    public static function undot(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            self::set($result, $key, $value);
        }

        return $result;
    }

    /**
     * Remove from an array all the occurrences of the given value
     *
     * @param array<array-key, TValue> $array
     *
     * @template TValue
     */
    public static function pull(array &$array, mixed $value): void
    {
        foreach ($array as $key => $v) {
            if ($v === $value) {
                unset($array[$key]);
            }
        }
    }

    /**
     * Remove a portion of the array and replace it with something else like `array_splice` but also preserve string keys from the replacement array
     *
     * @param array<TKey|TReplacementKey, TReplacementValue|TValue> $array
     * @param array<TReplacementKey, TReplacementValue>             $replacement
     *
     * @throws UnexpectedValueException If some keys in the replacement array are the same of the resulting array
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     * @template TReplacementKey of array-key
     * @template TReplacementValue
     */
    public static function splice(array &$array, int $offset, ?int $length = null, array $replacement = []): array
    {
        if (!self::isAssociative($replacement)) {
            return array_splice($array, $offset, $length, $replacement);
        }

        $replaced = array_slice($array, $offset, $length, true);

        // Normalize negative offsets
        if ($offset < 0) {
            $offset += count($array);
        }

        // Normalize negative lengths
        if ($length < 0) {
            $length = max(0, count($array) + $length - $offset);
        }

        $before = array_slice($array, 0, $offset, true);

        $after = $length === null ? [] : array_slice($array, $offset + $length, null, true);

        if (array_intersect_key($before, $replacement) || array_intersect_key($after, $replacement)) {
            throw new UnexpectedValueException(sprintf('Cannot replace %s items from offset %d: some keys in the replacement array are the same of the resulting array', $length, $offset));
        }

        $array = [...$before, ...$replacement, ...$after];

        return $replaced;
    }

    /**
     * Move an item from the given index to another
     *
     * @param array<TKey,TValue> $array
     * @param int                $fromIndex The source index to move from
     * @param int                $toIndex   The destination index to move to
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function moveItem(array &$array, int $fromIndex, int $toIndex): void
    {
        if ($toIndex !== $fromIndex) {
            self::splice($array, $toIndex, 0, self::splice($array, $fromIndex, 1));
        }
    }

    /**
     * Return an array of `[$key, $value]` pairs from the given array
     *
     * @param array<TKey, TValue> $array
     *
     * @return array<array{TKey, TValue}>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function entries(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[] = [$key, $value];
        }

        return $result;
    }

    /**
     * Get the array value at the given index,
     * negative indices are not allowed, use `Arr:at()` instead
     *
     * @param array<array-key, TValue> $array
     *
     * @return TValue|null
     *
     * @template TValue
     */
    public static function nth(array $array, int $index): mixed
    {
        return array_values($array)[$index] ?? null;
    }

    /**
     * Get the array value at the given index,
     * negative indices are allowed and start from the end
     *
     * @param array<array-key, TValue> $array
     *
     * @return TValue|null
     *
     * @template TValue
     */
    public static function at(array $array, int $index): mixed
    {
        return array_values($array)[$index >= 0 ? $index : count($array) + $index] ?? null;
    }

    /**
     * Get the index of the given value or null if not found
     *
     * @param array<mixed> $array
     */
    public static function indexOf(array $array, mixed $value): ?int
    {
        $index = array_search($value, array_values($array), true);
        return $index !== false ? $index : null;
    }

    /**
     * Get the key of the given value or null if not found
     *
     * @param array<TKey, TValue> $array
     *
     * @return TKey
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function keyOf(array $array, mixed $value): int|string|null
    {
        $key = array_search($value, $array, true);
        return $key !== false ? $key : null;
    }

    /**
     * Return the duplicate items of the array
     *
     * @param array<TKey, TValue> $array
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function duplicates(array $array): array
    {
        return array_diff_key($array, array_unique($array));
    }

    /**
     * Recursively append items from the second array that are missing in the first
     *
     * @param array<TKey, TValue> $array1
     * @param array<TKey, TValue> $array2
     *
     * @return array<TKey, array<TValue>|TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function appendMissing(array $array1, array $array2): array
    {
        foreach ($array1 as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (!array_key_exists($key, $array2)) {
                continue;
            }
            if (!is_array($array2[$key])) {
                continue;
            }
            $array1[$key] = self::appendMissing($array1[$key], $array2[$key]);
        }
        return $array1 + $array2;
    }

    /**
     * Return a random value from a given array
     *
     * @param array<array-key, TValue> $array
     * @param TValue|null              $default
     *
     * @return TValue|null
     *
     * @template TValue
     */
    public static function random(array $array, mixed $default = null): mixed
    {
        return $array !== [] ? $array[array_rand($array)] : $default;
    }

    /**
     * Return a given array with its values shuffled optionally preserving the key/value pairs
     *
     * @param array<TKey, TValue> $array
     * @param bool                $preserveKeys Whether to preserve the original keys
     *
     * @return ($preserveKeys is true ? array<TKey, TValue> : list<TValue>)
     *
     * @template TKey of array-key
     * @template TValue
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
     *
     * @param array<mixed> $array
     */
    public static function isAssociative(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Apply a callback to the given array and return the result
     *
     * The key of each element is passed to the callback as second argument
     *
     * @param array<TKey, TValue>             $array
     * @param callable(TValue, TKey): TReturn $callback
     *
     * @return array<TKey, TReturn>
     *
     * @template TKey of array-key
     * @template TValue
     * @template TReturn
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
     *
     * @param array<TKey, TValue>             $array
     * @param callable(TKey, TValue): TReturn $callback
     *
     * @return array<TReturn, TKey>
     *
     * @template TKey of array-key
     * @template TValue
     * @template TReturn of array-key
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
     *
     * @param array<TKey, TValue>          $array
     * @param callable(TValue, TKey): bool $callback
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Reject values from an array keeping only the values for which the callback returns `false`
     *
     * The key of each element is passed to the callback as second argument
     *
     * @param array<TKey, TValue>          $array
     * @param callable(TValue, TKey): bool $callback
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function reject(array $array, callable $callback): array
    {
        return self::filter($array, fn($value, $key) => !$callback($value, $key));
    }

    /**
     * Return whether every element of the array passes a test callback
     *
     * The key of each element is passed to the callback as second argument
     *
     * @param array<mixed> $array
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
     *
     * @param array<mixed> $array
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
     * Find the first element of an array passing a test callback
     *
     * The key of each element is passed to the callback as second argument
     *
     * @template TValue
     *
     * @param array<array-key, TValue> $array
     *
     * @return ?TValue
     */
    public static function find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Extract an array of values corresponding to the specified key from the given array
     *
     * The original array keys are preserved
     *
     * @param array<TKey, array<K, V>> $array
     *
     * @return array<TKey, V>
     *
     * @template TKey of array-key
     * @template K of array-key
     * @template V
     */
    public static function extract(array $array, string $key, mixed $default = null): array
    {
        return self::map($array, fn($value) => self::get(self::from($value), $key, $default));
    }

    /**
     * Group array items using the return value of the given callback
     *
     * @param array<TKey, TValue>             $array
     * @param callable(TValue, TKey): TReturn $callback
     *
     * @return array<string, list<TValue>>
     *
     * @template TKey of array-key
     * @template TReturn of string|Stringable
     * @template TValue
     */
    public static function group(array $array, callable $callback): array
    {
        $result = [];

        foreach (self::map($array, $callback) as $key => $value) {
            // Try to cast objects to string as `$value` will be used as key
            // in the resulting array
            if ($value instanceof Stringable) {
                $value = (string) $value;
            }

            $result[$value] ??= [];
            $result[$value][] = $array[$key];
        }

        return $result;
    }

    /**
     * Flatten array items up to the specified depth
     *
     * @param array<TKey, TValue> $array
     * @param int                 $depth Maximum depth to flatten
     *
     * @throws UnexpectedValueException If the depth parameter is negative
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        if ($depth === 0) {
            return $array;
        }

        if ($depth < 0) {
            throw new UnexpectedValueException(sprintf('%s() expects a non-negative depth', __METHOD__));
        }

        $result = [];

        foreach ($array as $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
            if ($value instanceof Traversable) {
                $value = iterator_to_array($value);
            }

            if (!is_array($value)) {
                $result[] = $value;
                continue;
            }

            if ($depth > 1) {
                $value = self::flatten($value, $depth - 1);
            }
            foreach ($value as $val) {
                $result[] = $val;
            }
        }

        return $result;
    }

    /**
     * Sort an array with the given options
     *
     * @param array<TKey, TValue>                                    $array
     * @param \SORT_ASC|\SORT_DESC                                   $direction     Direction of sorting. Possible values are `SORT_ASC` and `SORT_DESC`.
     * @param \SORT_NATURAL|\SORT_NUMERIC|\SORT_REGULAR|\SORT_STRING $type          Type of sorting. Possible values are `SORT_NATURAL`, `SORT_NUMERIC`, `SORT_REGULAR` and `SORT_STRING`.
     * @param array<TKey, mixed>|callable(TValue, TValue): int|null  $sortBy        A callback or second array of values used to sort the first
     * @param                                                        $caseSensitive Whether to perform a case-sensitive sorting
     * @param                                                        $preserveKeys  Whether to preserve array keys after sorting
     *
     * @throws UnexpectedValueException If the direction parameter is not SORT_ASC or SORT_DESC
     * @throws UnexpectedValueException If the type parameter is not one of the supported sorting types
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function sort(
        array $array,
        int $direction = SORT_ASC,
        int $type = SORT_NATURAL,
        array|callable|null $sortBy = null,
        bool $caseSensitive = false,
        bool $preserveKeys = true,
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
            return $array;
        }

        if ($sortBy === null) {
            $function = $direction === SORT_ASC
                ? ($preserveKeys ? 'asort' : 'sort')
                : ($preserveKeys ? 'arsort' : 'rsort');
            $function($array, $flags);
            return $array;
        }

        if (count($sortBy) !== count($array)) {
            throw new UnexpectedValueException('Cannot sort array: the $sortBy array must have the same number of items as the array to sort');
        }

        $function = $direction === SORT_ASC ? 'asort' : 'arsort';
        $function($sortBy, $flags);

        $result = [];

        foreach (array_keys($sortBy) as $key) {
            if (!array_key_exists($key, $array)) {
                throw new UnexpectedValueException(sprintf('Cannot sort array: key %s from the $sortBy array is not present in the array to sort', is_string($key) ? Str::wrap($key, '"') : $key));
            }

            if ($preserveKeys) {
                $result[$key] = $array[$key];
            } else {
                $result[] = $array[$key];
            }
        }

        return $result;
    }

    /**
     * Try to convert the given object to array
     *
     * @throws UnexpectedValueException If the object cannot be converted to an array
     *
     * @return ($object is array<TKey, TValue>|Traversable<TKey, TValue> ? array<TKey, TValue> : array<mixed>)
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function from(mixed $object): array
    {
        if (is_array($object)) {
            return $object;
        }
        if ($object instanceof Arrayable) {
            return $object->toArray();
        }
        if ($object instanceof Traversable) {
            return iterator_to_array($object);
        }
        throw new UnexpectedValueException(sprintf('Cannot convert to array an object of type %s', get_debug_type($object)));
    }

    /**
     * Create an array from `[$key, $value]` pairs
     *
     * @param array<array{TKey, TValue}> $entries
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public static function fromEntries(array $entries): array
    {
        $result = [];

        foreach ($entries as [$key, $value]) {
            $result[$key] = $value;
        }

        return $result;
    }
}
