<?php

namespace Formwork\Images\Avif;

use UnexpectedValueException;

/**
 * Fluent builder of big-endian binary data
 *
 * @since 2.3.0
 */
final class ByteWriter
{
    private string $data = '';

    /**
     * Append an unsigned big-endian integer of the given size in bytes (0 to 8)
     */
    public function uint(int $value, int $size): self
    {
        if ($size > 8 || $value < 0 || ($size < 8 && $value >= 1 << (8 * $size))) {
            throw new UnexpectedValueException(sprintf('Value %d does not fit in %d bytes', $value, $size));
        }

        $this->data .= $size === 0 ? '' : substr(pack('J', $value), 8 - $size);

        return $this;
    }

    public function bytes(string $bytes): self
    {
        $this->data .= $bytes;

        return $this;
    }

    public function toString(): string
    {
        return $this->data;
    }
}
