<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Decoder\DecoderInterface;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use UnexpectedValueException;

/**
 * ISO base media file containing items, such as AVIF and HEIF images
 *
 * The container keeps the boxes of the file (except the media data ones) and lets
 * items be added or removed. The file is rebuilt in a single step by {@see self::toString()},
 * which lays out item data in a single media data box and recomputes all the offsets, so
 * the changes never leave dangling offsets or unreferenced data behind.
 *
 * @since 2.4.0
 */
final class Container
{
    /**
     * Type of the media data box
     */
    private const string MEDIA_DATA_TYPE = 'mdat';

    /**
     * Type of the movie box, present in files containing image sequences
     */
    private const string MOVIE_TYPE = 'moov';

    /**
     * Boxes of the file, without media data
     *
     * @var list<Box|MetaBox>
     */
    private readonly array $boxes;

    /**
     * Position at which the media data box has to be placed
     */
    private readonly int $mediaDataPosition;

    private readonly bool $hasMovie;

    private readonly string $majorBrand;

    /**
     * Data of the items added to the container
     *
     * @var array<int, string>
     */
    private array $addedData = [];

    /**
     * @param list<Box> $boxes
     */
    private function __construct(
        private readonly string $source,
        array $boxes,
    ) {
        $types = array_map(static fn(Box $box): string => $box->type, $boxes);
        $position = array_search(self::MEDIA_DATA_TYPE, $types, true);

        $this->mediaDataPosition = $position === false ? count($boxes) : $position;
        $this->hasMovie = in_array(self::MOVIE_TYPE, $types, true);
        $this->majorBrand = substr($boxes[0]->payload, 0, 4);
        $this->boxes = self::withoutMediaData($boxes);
    }

    /**
     * Parse a file using the given decoder
     */
    public static function parse(DecoderInterface $decoder, string $data): self
    {
        $boxes = [];

        foreach ($decoder->decode($data) as $decoded) {
            $boxes[] = new Box((string) $decoded['type'], (string) $decoded['value']);
        }

        return new self($data, $boxes);
    }

    public function majorBrand(): string
    {
        return $this->majorBrand;
    }

    /**
     * Get the timeline of the image sequence, or null if the file does not contain one
     */
    public function imageSequence(): ?ImageSequence
    {
        foreach ($this->boxes as $box) {
            if ($box instanceof Box && $box->type === ImageSequence::TYPE) {
                return ImageSequence::fromBox($box);
            }
        }

        return null;
    }

    public function meta(): ?MetaBox
    {
        foreach ($this->boxes as $box) {
            if ($box instanceof MetaBox) {
                return $box;
            }
        }

        return null;
    }

    /**
     * @throws UnsupportedFeatureException If the file does not contain a meta box
     */
    public function requireMeta(): MetaBox
    {
        return $this->meta() ?? throw new UnsupportedFeatureException('AVIF file does not contain a meta box');
    }

    /**
     * Get the identifiers of all items of the given type
     *
     * @return list<int>
     */
    public function itemIdsByType(string $type): array
    {
        return $this->meta()?->find(ItemInfoBox::class)?->idsByType($type) ?? [];
    }

    /**
     * Get the data of an item, or null if the item does not exist or its data cannot be read
     */
    public function itemData(int $id): ?string
    {
        $location = $this->meta()?->find(ItemLocationBox::class)?->find($id);

        return $location === null ? null : ($this->addedData[$id] ?? $this->readLocation($location));
    }

    /**
     * Add an item and return its identifier
     */
    public function addItem(string $type, string $data): int
    {
        $meta = $this->requireMeta();
        $info = $meta->require(ItemInfoBox::class);
        $id = $info->nextId();

        $info->add(ItemInfoEntry::create($id, $type));
        $meta->require(ItemLocationBox::class)->set(new ItemLocation($id, 0, 0, [new Extent(0, strlen($data))]));
        $this->addedData[$id] = $data;

        return $id;
    }

    /**
     * Add a reference between two items
     */
    public function addReference(string $type, int $from, int $to): void
    {
        $meta = $this->requireMeta();
        $references = $meta->find(ItemReferenceBox::class);

        if ($references === null) {
            $references = new ItemReferenceBox();
            $meta->add($references);
        }

        $references->add($type, $from, $to);
    }

    /**
     * Remove an item together with its data and references
     */
    public function removeItem(int $id): void
    {
        $meta = $this->requireMeta();

        $meta->require(ItemInfoBox::class)->remove($id);
        $meta->require(ItemLocationBox::class)->remove($id);
        $meta->find(ItemReferenceBox::class)?->removeItem($id);

        unset($this->addedData[$id]);
    }

    /**
     * Rebuild the file
     *
     * @throws UnsupportedFeatureException If the file contains an image sequence
     */
    public function toString(): string
    {
        if ($this->hasMovie) {
            throw new UnsupportedFeatureException('Modifying AVIF image sequences is not supported');
        }

        $meta = $this->requireMeta();
        $locations = $meta->require(ItemLocationBox::class);

        [$payload, $idat, $items] = $this->layoutItemData($locations);

        $mediaData = new Box(self::MEDIA_DATA_TYPE, $payload);
        $provisional = $meta->withItemData($locations->withItems($items), $idat);
        $offset = $this->mediaDataPayloadOffset($provisional, $mediaData);
        $final = $meta->withItemData($locations->withItems(self::shiftItems($items, $offset)), $idat);

        return Box::encodeAll($this->sequence($final, $mediaData));
    }

    /**
     * @param list<Box> $boxes
     *
     * @return list<Box|MetaBox>
     */
    private static function withoutMediaData(array $boxes): array
    {
        $kept = array_filter($boxes, static fn(Box $box): bool => $box->type !== self::MEDIA_DATA_TYPE);

        return array_values(array_map(static fn(Box $box): Box|MetaBox => $box->type === MetaBox::TYPE ? MetaBox::fromBox($box) : $box, $kept));
    }

    /**
     * @param array<int, ItemLocation> $items
     *
     * @return array<int, ItemLocation>
     */
    private static function shiftItems(array $items, int $delta): array
    {
        return array_map(static fn(ItemLocation $item): ItemLocation => $item->isInFile() ? $item->shifted($delta) : $item, $items);
    }

    /**
     * @param list<Extent> $extents
     *
     * @throws UnexpectedValueException    If an extent is not valid
     * @throws UnsupportedFeatureException If an extent has no explicit length
     */
    private static function readExtents(string $source, array $extents): string
    {
        $data = '';

        foreach ($extents as $extent) {
            if ($extent->length === 0) {
                throw new UnsupportedFeatureException('Item extents without an explicit length are not supported');
            }

            if ($extent->offset + $extent->length > strlen($source)) {
                throw new UnexpectedValueException('Item extent exceeds the available data');
            }

            $data .= substr($source, $extent->offset, $extent->length);
        }

        return $data;
    }

    private function readLocation(ItemLocation $location): ?string
    {
        $source = $this->sourceOf($location);

        return $source === null ? null : self::readExtents($source, $location->extents);
    }

    /**
     * Get the data the extents of an item refer to
     */
    private function sourceOf(ItemLocation $location): ?string
    {
        if ($location->isInFile()) {
            return $this->source;
        }

        return $location->isInIdat() ? $this->meta()?->idat() : null;
    }

    /**
     * Place the data of all items stored in the file or in the item data box one after the other
     *
     * @return array{string, string, array<int, ItemLocation>} Media data payload, item data box payload and item locations relative to their start
     */
    private function layoutItemData(ItemLocationBox $locations): array
    {
        $file = new DataArea();
        $idat = new DataArea();
        $items = [];

        foreach ($locations->items() as $id => $item) {
            $area = $item->isInFile() ? $file : ($item->isInIdat() ? $idat : null);
            $items[$id] = $area === null ? $item : $item->withExtents($area->place($this->addedData[$id] ?? $this->readLocation($item) ?? ''));
        }

        return [$file->payload(), $idat->payload(), $items];
    }

    /**
     * Get the position of the media data payload in the rebuilt file
     */
    private function mediaDataPayloadOffset(MetaBox $meta, Box $mediaData): int
    {
        $preceding = array_slice($this->sequence($meta, $mediaData), 0, $this->mediaDataPosition);
        $length = array_sum(array_map(static fn(Box $box): int => $box->length(), $preceding));

        return $length + $mediaData->headerLength();
    }

    /**
     * Get the boxes of the rebuilt file
     *
     * @return list<Box>
     */
    private function sequence(MetaBox $meta, Box $mediaData): array
    {
        $boxes = array_map(static fn(Box|MetaBox $box): Box => $box instanceof MetaBox ? $meta->toBox() : $box, $this->boxes);
        array_splice($boxes, $this->mediaDataPosition, 0, [$mediaData]);

        return $boxes;
    }
}
