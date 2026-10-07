<?php

namespace Formwork\Images\Handler\Utils\Avif;

use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;

/**
 * Item location box (`iloc`)
 *
 * Extents pointing to the file (construction method 0) are bound to the box containing their data, so
 * that their offsets can be recomputed whatever the changes made to the layout of the file.
 *
 * @since 2.3.0
 */
final class ItemLocation
{
    /**
     * @param array<int, array{method: int, dataReference: int, baseOffset: int, extents: list<Extent>}> $items Item locations indexed by item ID
     */
    public function __construct(
        private int $version,
        private int $offsetSize,
        private int $lengthSize,
        private int $baseOffsetSize,
        private int $indexSize,
        private array $items,
    ) {}

    public static function parse(string $body): self
    {
        $reader = new ByteReader($body);

        $version = $reader->uint(1);
        $reader->skip(3);

        if ($version > 2) {
            throw new UnsupportedFeatureException(sprintf('Unsupported iloc box version %d', $version));
        }

        $sizes = $reader->uint(2);
        $offsetSize = $sizes >> 12;
        $lengthSize = ($sizes >> 8) & 0xF;
        $baseOffsetSize = ($sizes >> 4) & 0xF;
        $indexSize = $version === 0 ? 0 : $sizes & 0xF;

        if (max($offsetSize, $lengthSize, $baseOffsetSize, $indexSize) > 8) {
            throw new UnsupportedFeatureException('Unsupported iloc field size');
        }

        $idSize = $version < 2 ? 2 : 4;
        $items = [];

        for ($count = $reader->uint($idSize); $count > 0; $count--) {
            $id = $reader->uint($idSize);
            $method = $version === 0 ? 0 : $reader->uint(2) & 0xF;
            $dataReference = $reader->uint(2);
            $baseOffset = $reader->uint($baseOffsetSize);
            $extents = [];

            for ($extentCount = $reader->uint(2); $extentCount > 0; $extentCount--) {
                $extents[] = new Extent($reader->uint($indexSize), $reader->uint($offsetSize), $reader->uint($lengthSize));
            }

            $items[$id] = ['method' => $method, 'dataReference' => $dataReference, 'baseOffset' => $baseOffset, 'extents' => $extents];
        }

        return new self($version, $offsetSize, $lengthSize, $baseOffsetSize, $indexSize, $items);
    }

    public function writeTo(Box $box): void
    {
        $idSize = $this->version < 2 ? 2 : 4;

        $writer = (new ByteWriter())
            ->uint($this->version, 1)
            ->uint(0, 3)
            ->uint(($this->offsetSize << 12) | ($this->lengthSize << 8) | ($this->baseOffsetSize << 4) | $this->indexSize, 2)
            ->uint(count($this->items), $idSize);

        foreach ($this->items as $id => $item) {
            $writer->uint($id, $idSize);

            if ($this->version > 0) {
                $writer->uint($item['method'], 2);
            }

            $writer->uint($item['dataReference'], 2)->uint($item['baseOffset'], $this->baseOffsetSize)->uint(count($item['extents']), 2);

            foreach ($item['extents'] as $extent) {
                $writer->uint($extent->index, $this->indexSize)->uint($extent->offset, $this->offsetSize)->uint($extent->length, $this->lengthSize);
            }
        }

        $box->body = $writer->toString();
    }

    /**
     * Bind the extents pointing to the file to the boxes containing their data
     *
     * @param list<Box> $boxes Top-level boxes, as found in the parsed file
     */
    public function bind(array $boxes): void
    {
        foreach ($this->items as $item) {
            foreach ($item['extents'] as $extent) {
                $position = $item['baseOffset'] + $extent->offset;

                foreach ($boxes as $box) {
                    if ($item['method'] === 0 && $box->bodyOffset !== null && $position >= $box->bodyOffset && $position + $extent->length <= $box->bodyOffset + strlen($box->body)) {
                        $extent->box = $box;
                        $extent->anchor = $position - $box->bodyOffset;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Check whether the offsets of all the extents pointing to the file can be updated
     */
    public function isRelocatable(): bool
    {
        foreach ($this->items as $item) {
            foreach ($item['extents'] as $extent) {
                if ($item['method'] === 0 && $extent->length > 0 && $extent->box === null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Update the offsets of the bound extents after the layout of the file has changed
     *
     * @param callable(Box): int $bodyOffset Get the new offset of the payload of a box
     */
    public function relocate(callable $bodyOffset): void
    {
        foreach ($this->items as $item) {
            foreach ($item['extents'] as $extent) {
                if ($extent->box !== null) {
                    $extent->offset = $bodyOffset($extent->box) + $extent->anchor - $item['baseOffset'];
                }
            }
        }
    }

    /**
     * Get the extents of an item
     *
     * @return list<Extent>|null
     */
    public function extents(int $itemId): ?array
    {
        return $this->items[$itemId]['extents'] ?? null;
    }

    /**
     * Get the construction method of an item
     */
    public function method(int $itemId): ?int
    {
        return $this->items[$itemId]['method'] ?? null;
    }

    /**
     * Add an item stored in the payload of a box
     */
    public function add(int $itemId, Box $box, int $anchor, int $length): void
    {
        $this->items[$itemId] = ['method' => 0, 'dataReference' => 0, 'baseOffset' => 0, 'extents' => [new Extent(0, 0, $length, $box, $anchor)]];
    }

    public function remove(int $itemId): void
    {
        unset($this->items[$itemId]);
    }

    /**
     * Update the extents following a range removed from the payload of a box
     */
    public function shrink(Box $box, int $anchor, int $length): void
    {
        foreach ($this->items as $item) {
            foreach ($item['extents'] as $extent) {
                if ($extent->box === $box && $extent->anchor >= $anchor + $length) {
                    $extent->anchor -= $length;
                }
            }
        }
    }
}
