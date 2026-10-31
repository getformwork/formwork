<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use UnexpectedValueException;

/**
 * Item location box (iloc)
 *
 * The box is always written with a canonical layout (no base offsets, 32-bit extent
 * lengths), so its encoded size depends only on the number of items and extents and
 * never on the offset values.
 *
 * @since 2.4.0
 */
final class ItemLocationBox implements Encodable
{
    public const string TYPE = 'iloc';

    /**
     * Size in bytes of encoded extent lengths
     */
    private const int LENGTH_SIZE = 4;

    /**
     * Size in bytes of encoded extent indexes, when used
     */
    private const int INDEX_SIZE = 4;

    /**
     * @param array<int, ItemLocation> $items
     */
    public function __construct(
        private readonly int $version = 0,
        private readonly int $offsetSize = 4,
        private array $items = [],
    ) {}

    /**
     * @throws UnexpectedValueException If the box version is not supported
     */
    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);

        if ($version > 2) {
            throw new UnexpectedValueException(sprintf('Unsupported iloc box version %d', $version));
        }

        $reader->skip(3);
        $sizes = self::readSizes($reader, $version);
        $items = [];

        for ($count = $reader->uint(self::wideSize($version)); $count > 0; $count--) {
            $item = self::readItem($reader, $version, $sizes);
            $items[$item->id] = $item;
        }

        return new self($version, max($sizes['offset'], 4), $items);
    }

    /**
     * @return array<int, ItemLocation>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function find(int $id): ?ItemLocation
    {
        return $this->items[$id] ?? null;
    }

    public function set(ItemLocation $item): void
    {
        $this->items[$item->id] = $item;
    }

    public function remove(int $id): void
    {
        unset($this->items[$id]);
    }

    /**
     * Get a copy of the box with different items
     *
     * @param array<int, ItemLocation> $items
     */
    public function withItems(array $items): self
    {
        return new self($this->version, $this->offsetSize, $items);
    }

    public function toBox(): Box
    {
        $version = $this->encodedVersion();
        $indexSize = $version > 0 && $this->usesIndex() ? self::INDEX_SIZE : 0;

        $payload = Bytes::fullBox($version)
            . Bytes::pack(($this->offsetSize << 12) | (self::LENGTH_SIZE << 8) | $indexSize, 2)
            . Bytes::pack(count($this->items), self::wideSize($version));

        foreach ($this->items as $item) {
            $payload .= $this->encodeItem($item, $version, $indexSize);
        }

        return new Box(self::TYPE, $payload);
    }

    /**
     * Get the size of item identifiers and counts for the given version
     */
    private static function wideSize(int $version): int
    {
        return $version < 2 ? 2 : 4;
    }

    /**
     * @return array{offset: int, length: int, base: int, index: int}
     */
    private static function readSizes(ByteReader $reader, int $version): array
    {
        $packed = $reader->uint(2);

        return [
            'offset' => ($packed >> 12) & 0x0F,
            'length' => ($packed >> 8) & 0x0F,
            'base'   => ($packed >> 4) & 0x0F,
            'index'  => $version > 0 ? $packed & 0x0F : 0,
        ];
    }

    /**
     * @param array{offset: int, length: int, base: int, index: int} $sizes
     */
    private static function readItem(ByteReader $reader, int $version, array $sizes): ItemLocation
    {
        $id = $reader->uint(self::wideSize($version));
        $method = $version > 0 ? $reader->uint(2) & 0x0F : 0;
        $dataReferenceIndex = $reader->uint(2);
        $baseOffset = $reader->uint($sizes['base']);
        $extents = [];

        for ($count = $reader->uint(2); $count > 0; $count--) {
            $extents[] = self::readExtent($reader, $sizes, $baseOffset);
        }

        return new ItemLocation($id, $method, $dataReferenceIndex, $extents);
    }

    /**
     * @param array{offset: int, length: int, base: int, index: int} $sizes
     */
    private static function readExtent(ByteReader $reader, array $sizes, int $baseOffset): Extent
    {
        $index = $reader->uint($sizes['index']);
        $offset = $baseOffset + $reader->uint($sizes['offset']);

        return new Extent($offset, $reader->uint($sizes['length']), $index);
    }

    /**
     * Get the lowest version able to represent the items, since only the versions after the
     * first one can store construction methods and extent indexes
     */
    private function encodedVersion(): int
    {
        if (max([0, count($this->items), ...array_keys($this->items)]) > 0xFFFF) {
            return 2;
        }

        return $this->usesIndex() ? max($this->version, 1) : $this->version;
    }

    private function usesIndex(): bool
    {
        return array_filter($this->items, static fn(ItemLocation $item): bool => $item->usesIndex()) !== [];
    }

    private function encodeItem(ItemLocation $item, int $version, int $indexSize): string
    {
        $data = Bytes::pack($item->id, self::wideSize($version));

        if ($version > 0) {
            $data .= Bytes::pack($item->constructionMethod, 2);
        }

        $data .= Bytes::pack($item->dataReferenceIndex, 2) . Bytes::pack(count($item->extents), 2);

        foreach ($item->extents as $extent) {
            $data .= Bytes::pack($extent->index, $indexSize)
                . Bytes::pack($extent->offset, $this->offsetSize)
                . Bytes::pack($extent->length, self::LENGTH_SIZE);
        }

        return $data;
    }
}
