<?php

namespace Formwork\Images\Isobmff;

/**
 * Item properties box (iprp)
 *
 * @since 2.4.0
 */
final class ItemPropertiesBox implements Encodable
{
    public const string TYPE = 'iprp';

    /**
     * Type of the box containing the properties
     */
    private const string CONTAINER_TYPE = 'ipco';

    /**
     * @param list<Box>                    $properties
     * @param list<PropertyAssociationBox> $associations
     * @param list<Box>                    $others
     */
    public function __construct(
        private array $properties = [],
        private array $associations = [],
        private readonly array $others = [],
    ) {}

    public static function fromBox(Box $box): self
    {
        $properties = [];
        $associations = [];
        $others = [];

        foreach (Box::parseAll($box->payload) as $child) {
            match ($child->type) {
                self::CONTAINER_TYPE         => $properties = Box::parseAll($child->payload),
                PropertyAssociationBox::TYPE => $associations[] = PropertyAssociationBox::fromBox($child),
                default                      => $others[] = $child,
            };
        }

        return new self($properties, $associations, $others);
    }

    /**
     * Get all properties
     *
     * @return list<Box>
     */
    public function all(): array
    {
        return $this->properties;
    }

    /**
     * Get the properties associated with an item
     *
     * @return list<Box>
     */
    public function forItem(int $itemId): array
    {
        $properties = [];

        foreach ($this->associations as $association) {
            foreach ($association->indexesFor($itemId) as $index) {
                $properties[] = $this->properties[$index - 1] ?? null;
            }
        }

        return array_values(array_filter($properties));
    }

    /**
     * Add a property and associate it with an item
     */
    public function add(Box $property, int $itemId): void
    {
        $this->properties[] = $property;
        $this->primaryAssociations()->associate($itemId, count($this->properties));
    }

    /**
     * Remove all properties matching a predicate
     *
     * @param callable(Box): bool $predicate
     *
     * @return int Number of removed properties
     */
    public function removeWhere(callable $predicate): int
    {
        $keys = array_keys(array_filter($this->properties, $predicate));

        foreach (array_reverse($keys) as $key) {
            $this->removeAt($key + 1);
        }

        return count($keys);
    }

    public function toBox(): Box
    {
        $container = new Box(self::CONTAINER_TYPE, Box::encodeAll($this->properties));

        return new Box(self::TYPE, Box::encodeAll([$container, ...$this->associations, ...$this->others]));
    }

    /**
     * Remove the property with the given one-based index
     */
    private function removeAt(int $index): void
    {
        array_splice($this->properties, $index - 1, 1);

        foreach ($this->associations as $association) {
            $association->removeProperty($index);
        }
    }

    private function primaryAssociations(): PropertyAssociationBox
    {
        if ($this->associations === []) {
            $this->associations[] = new PropertyAssociationBox();
        }

        return $this->associations[0];
    }
}
