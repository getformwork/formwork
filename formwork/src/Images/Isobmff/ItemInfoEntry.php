<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * Item information entry (infe)
 *
 * @since 2.4.0
 */
final readonly class ItemInfoEntry implements Encodable
{
    public const string TYPE = 'infe';

    /**
     * Item type of Exif metadata
     */
    public const string EXIF_TYPE = 'Exif';

    public function __construct(
        public int $id,
        public ?string $type,
        private Box $box,
    ) {}

    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);
        $reader->skip(3);
        $id = $reader->uint($version >= 3 ? 4 : 2);

        if ($version < 2) {
            return new self($id, null, $box);
        }

        $reader->skip(2);

        return new self($id, $reader->bytes(4), $box);
    }

    /**
     * Create an entry for an item without a name
     */
    public static function create(int $id, string $type): self
    {
        $version = $id > 0xFFFF ? 3 : 2;

        $payload = Bytes::fullBox($version)
            . Bytes::pack($id, $version === 3 ? 4 : 2)
            . Bytes::pack(0, 2)
            . $type
            . "\x00";

        return new self($id, $type, new Box(self::TYPE, $payload));
    }

    public function toBox(): Box
    {
        return $this->box;
    }
}
