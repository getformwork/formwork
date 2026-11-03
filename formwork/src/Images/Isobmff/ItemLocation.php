<?php

namespace Formwork\Images\Isobmff;

/**
 * @since 2.4.0
 */
final readonly class ItemLocation
{
    /**
     * Construction method of items whose data is stored in the file
     */
    private const int METHOD_FILE = 0;

    /**
     * Construction method of items whose data is stored in the item data box
     */
    private const int METHOD_IDAT = 1;

    /**
     * @param list<Extent> $extents
     */
    public function __construct(
        public int $id,
        public int $constructionMethod = self::METHOD_FILE,
        public int $dataReferenceIndex = 0,
        public array $extents = [],
    ) {}

    /**
     * Return whether the item data is stored in the file itself
     */
    public function isInFile(): bool
    {
        return $this->constructionMethod === self::METHOD_FILE && $this->dataReferenceIndex === 0;
    }

    /**
     * Return whether the item data is stored in the item data box
     */
    public function isInIdat(): bool
    {
        return $this->constructionMethod === self::METHOD_IDAT && $this->dataReferenceIndex === 0;
    }

    /**
     * Return whether any extent uses an extent index
     */
    public function usesIndex(): bool
    {
        return array_filter($this->extents, static fn(Extent $extent): bool => $extent->index > 0) !== [];
    }

    /**
     * @param list<Extent> $extents
     */
    public function withExtents(array $extents): self
    {
        return new self($this->id, $this->constructionMethod, $this->dataReferenceIndex, $extents);
    }

    /**
     * Get a copy of the location with all extent offsets moved by the given amount
     */
    public function shifted(int $delta): self
    {
        return $this->withExtents(array_map(static fn(Extent $extent): Extent => $extent->shifted($delta), $this->extents));
    }
}
