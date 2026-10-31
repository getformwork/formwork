<?php

namespace Formwork\Images\Binary;

use UnexpectedValueException;

/**
 * @since 2.4.0
 */
final class Bytes
{
    /**
     * Pack an unsigned integer as a string of the given size
     *
     * @throws UnexpectedValueException If the value does not fit in the given size
     */
    public static function pack(int $value, int $size, Endianness $endianness = Endianness::Big): string
    {
        if ($value < 0 || $size < 0 || $size > 8 || ($size < 8 && $value >> (8 * $size) !== 0)) {
            throw new UnexpectedValueException(sprintf('Value %d does not fit in %d bytes', $value, $size));
        }

        $bytes = substr(pack('J', $value), 8 - $size);

        return $endianness === Endianness::Little ? strrev($bytes) : $bytes;
    }

    /**
     * Unpack an unsigned integer of the given size at the given offset
     *
     * @throws UnexpectedValueException If the data is too short or the integer does not fit in a PHP integer
     */
    public static function unpack(string $data, int $offset, int $size, Endianness $endianness = Endianness::Big): int
    {
        if ($offset < 0 || $size < 0 || $offset + $size > strlen($data)) {
            throw new UnexpectedValueException('Unexpected end of data');
        }

        $bytes = substr($data, $offset, $size);

        if ($endianness === Endianness::Little) {
            $bytes = strrev($bytes);
        }

        $value = 0;

        for ($i = 0; $i < $size; $i++) {
            if ($value >> 55 !== 0) {
                throw new UnexpectedValueException('Integer is too large');
            }
            $value = ($value << 8) | ord($bytes[$i]);
        }

        return $value;
    }

    /**
     * Pack the version and flags fields of a full box
     */
    public static function fullBox(int $version, int $flags = 0): string
    {
        return self::pack($version, 1) . self::pack($flags, 3);
    }

    /**
     * Apply a list of replacements to the given data
     *
     * Every replacement is a triple of offset, length of the replaced range and replacement.
     * Offsets always refer to the original data, so that replacements do not shift each other.
     *
     * @param list<array{int, int, string}> $replacements
     *
     * @throws UnexpectedValueException If a replacement is outside the data or overlaps another one
     */
    public static function replace(string $data, array $replacements): string
    {
        usort($replacements, static fn(array $a, array $b): int => $b[0] <=> $a[0]);

        $limit = strlen($data);

        foreach ($replacements as [$offset, $length, $replacement]) {
            if ($offset < 0 || $length < 0 || $offset + $length > $limit) {
                throw new UnexpectedValueException('Invalid replacement range');
            }
            $data = substr_replace($data, $replacement, $offset, $length);
            $limit = $offset;
        }

        return $data;
    }
}
