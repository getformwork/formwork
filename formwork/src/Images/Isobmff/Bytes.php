<?php

namespace Formwork\Images\Isobmff;

use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class Bytes
{
    /**
     * Pack an unsigned integer as a big-endian string of the given size
     *
     * @throws UnexpectedValueException If the value does not fit in the given size
     */
    public static function pack(int $value, int $size): string
    {
        if ($value < 0 || $value >> (8 * $size) !== 0) {
            throw new UnexpectedValueException(sprintf('Value %d does not fit in %d bytes', $value, $size));
        }

        return substr(pack('J', $value), 8 - $size);
    }

    /**
     * Pack the version and flags fields of a full box
     */
    public static function fullBox(int $version, int $flags = 0): string
    {
        return self::pack($version, 1) . self::pack($flags, 3);
    }
}
