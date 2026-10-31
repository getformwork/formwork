<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * Item reference box (iref)
 *
 * @since 2.4.0
 */
final class ItemReferenceBox implements Encodable
{
    public const string TYPE = 'iref';

    /**
     * Reference type linking a metadata item to the item it describes
     */
    public const string CONTENT_DESCRIBES = 'cdsc';

    /**
     * @param list<ItemReference> $references
     */
    public function __construct(
        private readonly int $version = 0,
        private array $references = [],
    ) {}

    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);
        $reader->skip(3);
        $idSize = self::idSize($version);

        $references = array_map(
            static fn(Box $child): ItemReference => ItemReference::fromBox($child, $idSize),
            Box::parseAll($reader->rest()),
        );

        return new self($version, $references);
    }

    public function add(string $type, int $from, int $to): void
    {
        $this->references[] = new ItemReference($type, $from, [$to]);
    }

    /**
     * Remove all the references from or to an item
     */
    public function removeItem(int $id): void
    {
        $references = array_map(static fn(ItemReference $reference): ?ItemReference => $reference->withoutItem($id), $this->references);
        $this->references = array_values(array_filter($references));
    }

    public function toBox(): Box
    {
        $version = $this->encodedVersion();
        $idSize = self::idSize($version);

        $children = array_map(static fn(ItemReference $reference): Box => $reference->toBox($idSize), $this->references);

        return new Box(self::TYPE, Bytes::fullBox($version) . Box::encodeAll($children));
    }

    private static function idSize(int $version): int
    {
        return $version === 0 ? 2 : 4;
    }

    /**
     * Get the lowest version able to represent the references
     */
    private function encodedVersion(): int
    {
        $ids = array_merge(...array_map(static fn(ItemReference $reference): array => $reference->ids(), $this->references));

        return max([0, ...$ids]) > 0xFFFF ? 1 : $this->version;
    }
}
