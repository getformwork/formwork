<?php

namespace Formwork\Images\Isobmff;

use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class ByteReader
{
    /**
     * Current read position
     */
    private int $position = 0;

    public function __construct(
        private readonly string $data,
    ) {}

    /**
     * Get the number of unread bytes
     */
    public function remaining(): int
    {
        return strlen($this->data) - $this->position;
    }

    /**
     * Read a given number of bytes
     *
     * @throws UnexpectedValueException If there are not enough bytes left
     */
    public function bytes(int $length): string
    {
        if ($length < 0 || $length > $this->remaining()) {
            throw new UnexpectedValueException('Unexpected end of data');
        }

        $bytes = substr($this->data, $this->position, $length);
        $this->position += $length;

        return $bytes;
    }

    /**
     * Read all the remaining bytes
     */
    public function rest(): string
    {
        return $this->bytes($this->remaining());
    }

    /**
     * Skip a given number of bytes
     */
    public function skip(int $length): void
    {
        $this->bytes($length);
    }

    /**
     * Read a big-endian unsigned integer of the given size (a size of 0 always reads 0)
     */
    public function uint(int $size): int
    {
        $bytes = $this->bytes($size);
        $value = 0;

        for ($i = 0; $i < $size; $i++) {
            $value = ($value << 8) | ord($bytes[$i]);
        }

        return $value;
    }
}
