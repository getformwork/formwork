<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;

/**
 * Item property association box (ipma)
 *
 * @since 2.4.0
 */
final class PropertyAssociationBox implements Encodable
{
    public const string TYPE = 'ipma';

    /**
     * Flag signalling that property indexes are stored as 15-bit values
     */
    private const int WIDE_INDEXES_FLAG = 1;

    /**
     * Highest property index representable with 7 bits
     */
    private const int MAX_NARROW_INDEX = 0x7F;

    /**
     * @param array<int, list<PropertyAssociation>> $entries Associations keyed by item identifier
     */
    public function __construct(
        private readonly int $version = 0,
        private readonly int $flags = 0,
        private array $entries = [],
    ) {}

    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);
        $flags = $reader->uint(3);
        $wide = ($flags & self::WIDE_INDEXES_FLAG) !== 0;
        $entries = [];

        for ($count = $reader->uint(4); $count > 0; $count--) {
            $entries[$reader->uint($version < 1 ? 2 : 4)] = self::readAssociations($reader, $wide);
        }

        return new self($version, $flags, $entries);
    }

    /**
     * Get the one-based indexes of the properties associated with an item
     *
     * @return list<int>
     */
    public function indexesFor(int $itemId): array
    {
        return array_map(static fn(PropertyAssociation $association): int => $association->index, $this->entries[$itemId] ?? []);
    }

    public function associate(int $itemId, int $index, bool $essential = false): void
    {
        $this->entries[$itemId][] = new PropertyAssociation($index, $essential);
    }

    /**
     * Remove all associations with a property and renumber the following ones
     */
    public function removeProperty(int $index): void
    {
        foreach ($this->entries as $itemId => $associations) {
            $this->entries[$itemId] = $this->withoutProperty($associations, $index);
        }
    }

    public function toBox(): Box
    {
        $version = max([0, ...array_keys($this->entries)]) > 0xFFFF ? 1 : $this->version;
        $wide = $this->usesWideIndexes();
        $flags = $wide ? $this->flags | self::WIDE_INDEXES_FLAG : $this->flags;

        $payload = Bytes::fullBox($version, $flags) . Bytes::pack(count($this->entries), 4);

        foreach ($this->entries as $itemId => $associations) {
            $payload .= Bytes::pack($itemId, $version < 1 ? 2 : 4) . $this->encodeAssociations($associations, $wide);
        }

        return new Box(self::TYPE, $payload);
    }

    /**
     * @return list<PropertyAssociation>
     */
    private static function readAssociations(ByteReader $reader, bool $wide): array
    {
        $associations = [];

        for ($count = $reader->uint(1); $count > 0; $count--) {
            $associations[] = PropertyAssociation::read($reader, $wide);
        }

        return $associations;
    }

    /**
     * @param list<PropertyAssociation> $associations
     *
     * @return list<PropertyAssociation>
     */
    private function withoutProperty(array $associations, int $index): array
    {
        $kept = [];

        foreach ($associations as $association) {
            if ($association->index !== $index) {
                $kept[] = $association->index > $index ? new PropertyAssociation($association->index - 1, $association->essential) : $association;
            }
        }

        return $kept;
    }

    /**
     * @param list<PropertyAssociation> $associations
     */
    private function encodeAssociations(array $associations, bool $wide): string
    {
        $data = Bytes::pack(count($associations), 1);

        foreach ($associations as $association) {
            $data .= $association->encode($wide);
        }

        return $data;
    }

    private function usesWideIndexes(): bool
    {
        $indexes = array_merge(...array_map(fn(array $associations): array => $this->indexesOf($associations), $this->entries));

        return ($this->flags & self::WIDE_INDEXES_FLAG) !== 0 || max([0, ...$indexes]) > self::MAX_NARROW_INDEX;
    }

    /**
     * @param list<PropertyAssociation> $associations
     *
     * @return list<int>
     */
    private function indexesOf(array $associations): array
    {
        return array_map(static fn(PropertyAssociation $association): int => $association->index, $associations);
    }
}
