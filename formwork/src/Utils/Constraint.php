<?php

namespace Formwork\Utils;

class Constraint
{
    /**
     * Values considered true when parsed as boolean
     */
    public const TRUTHY_VALUES = [true, 1, 'true', '1', 'on', 'yes'];

    /**
     * Values considered false when parsed as boolean
     */
    public const FALSY_VALUES = [false, 0, 'false', '0', 'off', 'no'];

    /**
     * Values considered null when parsed as such
     */
    public const EMPTY_VALUES = [null, '', []];

    /**
     * Return whether a value is considered true when parsed as boolean
     */
    public static function isTruthy($value): bool
    {
        return in_array($value, self::TRUTHY_VALUES, true);
    }

    /**
     * Return whether a value is considered false when parsed as boolean
     */
    public static function isFalsy($value): bool
    {
        return in_array($value, self::FALSY_VALUES, true);
    }

    /**
     * Return whether a value is considered empty
     */
    public static function isEmpty($value): bool
    {
        return in_array($value, self::EMPTY_VALUES, true);
    }

    /**
     * Return whether a value matches the specified regex pattern
     */
    public static function matchesRegex($value, string $regex): bool
    {
        return (bool) @preg_match(Str::wrap($regex, '/'), $value);
    }

    /**
     * Return whether a value is in the specified range
     */
    public static function isInRange(
        $value,
        int|float $start = PHP_FLOAT_MIN,
        int|float $end = PHP_FLOAT_MAX,
        bool $includeMin = true,
        bool $includeMax = true
    ): bool {
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        return ($includeMin ? $value >= $start : $value > $start) &&
            ($includeMax ? $value <= $end : $value < $end);
    }

    /**
     * Return whether an integer value is in the specified range
     */
    public static function isInIntegerRange(
        $value,
        int $start = PHP_INT_MIN,
        int $end = PHP_INT_MAX,
        int $step = 1,
        bool $includeMin = true,
        bool $includeMax = true
    ): bool {
        return static::isInRange((int) $value, $start, $end, $includeMin, $includeMax)
            && (($value - min($start, $end)) % $step === 0);
    }

    /**
     * Return whether a value is of the specified type
     */
    public static function isOfType($value, string $type, bool $unionTypes = false): bool
    {
        if ($unionTypes) {
            return Arr::some(explode('|', $type), fn ($type) => static::isOfType($value, $type, unionTypes: false));
        }
        if (is_object($value)) {
            return $value instanceof $type;
        }
        return get_debug_type($value) === $type;
    }
}
