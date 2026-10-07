<?php

namespace Formwork\Images\Avif;

use UnexpectedValueException;

/**
 * Sequential reader of big-endian binary data
 *
 * @since 2.3.0
 */
final class ByteReader
{
    private int $position = 0;

    public function __construct(
        private readonly string $data,
    ) {}

    public function isAtEnd(): bool
    {
        return $this->position >= strlen($this->data);
    }

    public function remaining(): int
    {
        return strlen($this->data) - $this->position;
    }

    public function skip(int $length): void
    {
        $this->bytes($length);
    }

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
     * Read an unsigned big-endian integer of the given size in bytes (0 to 8)
     */
    public function uint(int $size): int
    {
        $value = 0;

        foreach (str_split($this->bytes($size)) as $byte) {
            $value = ($value << 8) | ord($byte);
        }

        return $value;
    }

    /**
     * Read a null-terminated string
     */
    public function string(): string
    {
        $end = strpos($this->data, "\x00", $this->position);

        $string = $this->bytes(($end === false ? strlen($this->data) : $end) - $this->position);

        if ($end !== false) {
            $this->skip(1);
        }

        return $string;
    }
}
