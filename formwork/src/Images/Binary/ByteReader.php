<?php

namespace Formwork\Images\Binary;

use UnexpectedValueException;

/**
 * @since 2.4.0
 */
final class ByteReader
{
    /**
     * Current read position
     */
    private int $position = 0;

    public function __construct(
        private readonly string $data,
        int $position = 0,
    ) {
        $this->seek($position);
    }

    /**
     * Get the current read position
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * Move the read position to an absolute offset
     *
     * @throws UnexpectedValueException If the offset is outside the data
     */
    public function seek(int $position): void
    {
        if ($position < 0 || $position > strlen($this->data)) {
            throw new UnexpectedValueException('Unexpected end of data');
        }

        $this->position = $position;
    }

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
     * Get a given number of bytes without moving the read position
     *
     * @throws UnexpectedValueException If there are not enough bytes left
     */
    public function peek(int $length): string
    {
        $position = $this->position;
        $bytes = $this->bytes($length);
        $this->position = $position;

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
     * Read an unsigned integer of the given size (a size of 0 always reads 0)
     *
     * @throws UnexpectedValueException If there are not enough bytes left
     */
    public function uint(int $size, Endianness $endianness = Endianness::Big): int
    {
        return Bytes::unpack($this->bytes($size), 0, $size, $endianness);
    }

    /**
     * Read a null-terminated string, consuming the terminator
     *
     * @throws UnexpectedValueException If the string is not terminated
     */
    public function cString(): string
    {
        $end = strpos($this->data, "\x00", $this->position);

        if ($end === false) {
            throw new UnexpectedValueException('Unexpected end of data');
        }

        $string = $this->bytes($end - $this->position);
        $this->position++;

        return $string;
    }
}
