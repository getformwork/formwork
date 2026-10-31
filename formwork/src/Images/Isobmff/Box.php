<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * @since 2.4.0
 */
final readonly class Box implements Encodable
{
    /**
     * Length of the standard box header
     */
    private const int HEADER_LENGTH = 8;

    /**
     * Length of the box header when the 64-bit size is used
     */
    private const int LARGE_HEADER_LENGTH = 16;

    /**
     * Maximum box length representable with the 32-bit size
     */
    private const int MAX_LENGTH = 0xFFFFFFFF;

    public function __construct(
        public string $type,
        public string $payload = '',
    ) {}

    /**
     * Parse a sequence of boxes
     *
     * @return list<self>
     */
    public static function parseAll(string $data): array
    {
        $reader = new ByteReader($data);
        $boxes = [];

        while ($reader->remaining() > 0) {
            $boxes[] = self::read($reader);
        }

        return $boxes;
    }

    /**
     * Encode a sequence of boxes
     *
     * @param iterable<Encodable> $boxes
     */
    public static function encodeAll(iterable $boxes): string
    {
        $data = '';

        foreach ($boxes as $box) {
            $data .= $box->toBox()->encode();
        }

        return $data;
    }

    public function toBox(): self
    {
        return $this;
    }

    /**
     * Get the length of the box header
     */
    public function headerLength(): int
    {
        return strlen($this->payload) + self::HEADER_LENGTH > self::MAX_LENGTH ? self::LARGE_HEADER_LENGTH : self::HEADER_LENGTH;
    }

    /**
     * Get the length of the encoded box
     */
    public function length(): int
    {
        return $this->headerLength() + strlen($this->payload);
    }

    public function encode(): string
    {
        if ($this->headerLength() === self::HEADER_LENGTH) {
            return Bytes::pack($this->length(), 4) . $this->type . $this->payload;
        }

        return Bytes::pack(1, 4) . $this->type . Bytes::pack($this->length(), 8) . $this->payload;
    }

    private static function read(ByteReader $reader): self
    {
        $size = $reader->uint(4);
        $type = $reader->bytes(4);

        if ($size === 1) {
            return new self($type, $reader->bytes($reader->uint(8) - self::LARGE_HEADER_LENGTH));
        }

        if ($size === 0) {
            return new self($type, $reader->rest());
        }

        return new self($type, $reader->bytes($size - self::HEADER_LENGTH));
    }
}
