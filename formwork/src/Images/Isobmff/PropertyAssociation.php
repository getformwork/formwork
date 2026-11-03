<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * @since 2.4.0
 */
final readonly class PropertyAssociation
{
    /**
     * @param int  $index     One-based index of the property
     * @param bool $essential Whether the property is essential to process the item
     */
    public function __construct(
        public int $index,
        public bool $essential = false,
    ) {}

    public static function read(ByteReader $reader, bool $wide): self
    {
        $bits = self::indexBits($wide);
        $value = $reader->uint($wide ? 2 : 1);

        return new self($value & ((1 << $bits) - 1), ($value >> $bits) === 1);
    }

    public function encode(bool $wide): string
    {
        $flag = $this->essential ? 1 << self::indexBits($wide) : 0;

        return Bytes::pack($flag | $this->index, $wide ? 2 : 1);
    }

    private static function indexBits(bool $wide): int
    {
        return $wide ? 15 : 7;
    }
}
