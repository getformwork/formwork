<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;

/**
 * Meta box (meta)
 *
 * @since 2.4.0
 */
final class MetaBox implements Encodable
{
    public const string TYPE = 'meta';

    /**
     * Type of the box storing item data inside the meta box
     */
    private const string ITEM_DATA_TYPE = 'idat';

    /**
     * Boxes parsed into dedicated classes
     */
    private const array PARSED_BOXES = [
        ItemInfoBox::TYPE       => ItemInfoBox::class,
        ItemLocationBox::TYPE   => ItemLocationBox::class,
        ItemPropertiesBox::TYPE => ItemPropertiesBox::class,
        ItemReferenceBox::TYPE  => ItemReferenceBox::class,
    ];

    /**
     * @param string          $header   Version and flags of the box
     * @param list<Encodable> $children
     */
    public function __construct(
        private readonly string $header,
        private array $children,
    ) {}

    public static function fromBox(Box $box): self
    {
        $reader = new ByteReader($box->payload);
        $header = $reader->bytes(4);

        return new self($header, array_map(self::parseChild(...), Box::parseAll($reader->rest())));
    }

    /**
     * Find the first child of the given class
     *
     * @template T of Encodable
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function find(string $class): ?Encodable
    {
        foreach ($this->children as $child) {
            if ($child instanceof $class) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Get the first child of the given class
     *
     * @template T of Encodable
     *
     * @param class-string<T> $class
     *
     * @throws UnsupportedFeatureException If the child does not exist
     *
     * @return T
     */
    public function require(string $class): Encodable
    {
        return $this->find($class) ?? throw new UnsupportedFeatureException(sprintf('AVIF file does not contain a required "%s" box', constant($class . '::TYPE')));
    }

    public function add(Encodable $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Get a copy of the box with a different item location box and item data box content
     */
    public function withItemData(ItemLocationBox $locations, string $idat): self
    {
        $children = array_map(static fn(Encodable $child): ?Encodable => self::replaceItemData($child, $locations, $idat), $this->children);

        return new self($this->header, array_values(array_filter($children)));
    }

    /**
     * Get the identifier of the primary item
     */
    public function primaryItemId(): ?int
    {
        $box = $this->findGeneric('pitm');

        if ($box === null) {
            return null;
        }

        $reader = new ByteReader($box->payload);
        $version = $reader->uint(1);
        $reader->skip(3);

        return $reader->uint($version === 0 ? 2 : 4);
    }

    /**
     * @throws UnsupportedFeatureException If the primary item is not defined
     */
    public function requirePrimaryItemId(): int
    {
        return $this->primaryItemId() ?? throw new UnsupportedFeatureException('AVIF file does not define a primary item');
    }

    /**
     * Get the content of the item data box
     */
    public function idat(): ?string
    {
        return $this->findGeneric(self::ITEM_DATA_TYPE)?->payload;
    }

    public function toBox(): Box
    {
        return new Box(self::TYPE, $this->header . Box::encodeAll($this->children));
    }

    /**
     * Replace the item location box and the content of the item data box, which is dropped when empty
     */
    private static function replaceItemData(Encodable $child, ItemLocationBox $locations, string $idat): ?Encodable
    {
        if ($child instanceof ItemLocationBox) {
            return $locations;
        }

        if (!$child instanceof Box || $child->type !== self::ITEM_DATA_TYPE) {
            return $child;
        }

        return $idat === '' ? null : new Box(self::ITEM_DATA_TYPE, $idat);
    }

    private static function parseChild(Box $box): Encodable
    {
        $class = self::PARSED_BOXES[$box->type] ?? null;

        return $class === null ? $box : $class::fromBox($box);
    }

    private function findGeneric(string $type): ?Box
    {
        foreach ($this->children as $child) {
            if ($child instanceof Box && $child->type === $type) {
                return $child;
            }
        }

        return null;
    }
}
