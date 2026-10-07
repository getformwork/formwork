<?php

namespace Formwork\Images\Avif;

/**
 * Item property association box (`ipma`)
 *
 * @since 2.3.0
 */
final class PropertyAssociations
{
    /**
     * @param array<int, list<array{index: int, essential: bool}>> $items Associations indexed by item ID. Properties are indexed starting from 1
     */
    public function __construct(
        private int $version,
        private int $flags,
        private array $items,
    ) {}

    public static function parse(string $body): self
    {
        $reader = new ByteReader($body);

        $version = $reader->uint(1);
        $flags = $reader->uint(3);
        $items = [];

        // The lowest flag bit selects 15-bit property indexes instead of 7-bit ones
        $associationSize = ($flags & 1) !== 0 ? 2 : 1;
        $essentialBit = 1 << (8 * $associationSize - 1);

        for ($count = $reader->uint(4); $count > 0; $count--) {
            $id = $reader->uint($version === 0 ? 2 : 4);

            for ($associations = $reader->uint(1); $associations > 0; $associations--) {
                $association = $reader->uint($associationSize);
                $items[$id][] = ['index' => $association & ($essentialBit - 1), 'essential' => ($association & $essentialBit) !== 0];
            }
        }

        return new self($version, $flags, $items);
    }

    public function serialize(): string
    {
        $indexes = array_column(array_merge(...array_values($this->items)), 'index');
        $version = max([$this->version, ...array_keys($this->items)]) > 0xFFFF ? 1 : $this->version;
        $flags = max([0, ...$indexes]) > 0x7F ? $this->flags | 1 : $this->flags;
        $associationSize = ($flags & 1) !== 0 ? 2 : 1;
        $essentialBit = 1 << (8 * $associationSize - 1);

        $writer = (new ByteWriter())->uint($version, 1)->uint($flags, 3)->uint(count($this->items), 4);

        foreach ($this->items as $id => $associations) {
            $writer->uint($id, $version === 0 ? 2 : 4)->uint(count($associations), 1);

            foreach ($associations as $association) {
                $writer->uint($association['index'] | ($association['essential'] ? $essentialBit : 0), $associationSize);
            }
        }

        return $writer->toString();
    }

    /**
     * Get the indexes of the properties associated to an item
     *
     * @return list<int>
     */
    public function propertiesOf(int $itemId): array
    {
        return array_column($this->items[$itemId] ?? [], 'index');
    }

    public function associate(int $itemId, int $index): void
    {
        $this->items[$itemId][] = ['index' => $index, 'essential' => false];
    }

    /**
     * Remove all the associations of an item
     */
    public function removeItem(int $itemId): void
    {
        unset($this->items[$itemId]);
    }

    /**
     * Remove a property, shifting the indexes of the following ones
     */
    public function removeProperty(int $index): void
    {
        foreach ($this->items as $id => $associations) {
            $this->items[$id] = [];

            foreach ($associations as $association) {
                if ($association['index'] !== $index) {
                    $association['index'] -= $association['index'] > $index ? 1 : 0;
                    $this->items[$id][] = $association;
                }
            }
        }
    }
}
