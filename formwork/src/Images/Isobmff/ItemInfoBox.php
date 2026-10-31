<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * Item information box (iinf)
 *
 * @since 2.4.0
 */
final class ItemInfoBox implements Encodable
{
    public const string TYPE = 'iinf';

    /**
     * @param list<ItemInfoEntry> $entries
     */
    public function __construct(
        private readonly int $version = 0,
        private array $entries = [],
    ) {}

    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);
        $reader->skip(3);
        $reader->skip($version === 0 ? 2 : 4);

        $entries = [];

        foreach (Box::parseAll($reader->rest()) as $child) {
            if ($child->type === ItemInfoEntry::TYPE) {
                $entries[] = ItemInfoEntry::fromBox($child);
            }
        }

        return new self($version, $entries);
    }

    /**
     * Get the identifiers of all items of the given type
     *
     * @return list<int>
     */
    public function idsByType(string $type): array
    {
        $entries = array_filter($this->entries, static fn(ItemInfoEntry $entry): bool => $entry->type === $type);

        return array_values(array_map(static fn(ItemInfoEntry $entry): int => $entry->id, $entries));
    }

    /**
     * Get an identifier higher than the ones used by all items
     */
    public function nextId(): int
    {
        return max([0, ...array_map(static fn(ItemInfoEntry $entry): int => $entry->id, $this->entries)]) + 1;
    }

    public function add(ItemInfoEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    public function remove(int $id): void
    {
        $this->entries = array_values(array_filter($this->entries, static fn(ItemInfoEntry $entry): bool => $entry->id !== $id));
    }

    public function toBox(): Box
    {
        $version = count($this->entries) > 0xFFFF ? 1 : $this->version;

        $payload = Bytes::fullBox($version)
            . Bytes::pack(count($this->entries), $version === 0 ? 2 : 4)
            . Box::encodeAll($this->entries);

        return new Box(self::TYPE, $payload);
    }
}
