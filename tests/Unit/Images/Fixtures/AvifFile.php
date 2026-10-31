<?php

namespace Formwork\Tests\Unit\Images\Fixtures;

/**
 * Builder of AVIF files with a controlled box layout
 *
 * Encoders only produce a narrow subset of the layouts the format allows, so the files
 * built here cover the variants they leave out, such as items stored in the item data
 * box, wider box versions and media data boxes placed before the metadata.
 */
final class AvifFile
{
    /**
     * Identifier of the primary item
     */
    public const int PRIMARY_ID = 1;

    /**
     * Identifier of the auxiliary item holding the alpha channel
     */
    public const int ALPHA_ID = 2;

    /**
     * Identifier of the item holding Exif metadata
     */
    public const int EXIF_ID = 3;

    /**
     * Data of the primary item, standing in for a coded image
     */
    public const string PRIMARY_DATA = 'primary-item-data';

    /**
     * Data of the auxiliary item holding the alpha channel
     */
    public const string ALPHA_DATA = 'alpha-item-data';

    /**
     * Auxiliary type of alpha channel items
     */
    private const string ALPHA_AUX_TYPE = 'urn:mpeg:mpegB:cicp:systems:auxiliary:alpha';

    private string $brand = 'avif';

    private int $width = 64;

    private int $height = 48;

    private bool $dimensions = true;

    private int $depth = 8;

    private ?string $colorProfile = null;

    private ?string $exif = null;

    private bool $alpha = false;

    private bool $sequence = false;

    private ?int $frames = null;

    private int $plays = 1;

    private ?bool $monochrome = null;

    private bool $freeSpace = false;

    private int $locationVersion = 0;

    private int $baseOffsetSize = 0;

    private bool $extentIndexes = false;

    private bool $itemDataBox = false;

    private bool $mediaDataFirst = false;

    private bool $largeMediaDataBox = false;

    private bool $unsizedMediaDataBox = false;

    private bool $primaryItem = true;

    private bool $itemProperties = true;

    private bool $wideAssociations = false;

    private int $fillerProperties = 0;

    /**
     * @var array<int, array{offset: int|null, length: int|null}>
     */
    private array $extentOverrides = [];

    /**
     * @var array<int, int>
     */
    private array $dataReferences = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Encode a box with the standard 32-bit size
     */
    public static function box(string $type, string $payload = ''): string
    {
        return pack('N', 8 + strlen($payload)) . $type . $payload;
    }

    /**
     * Encode a box with the 64-bit size
     */
    public static function largeBox(string $type, string $payload = ''): string
    {
        return pack('N', 1) . $type . pack('J', 16 + strlen($payload)) . $payload;
    }

    /**
     * Encode a box without an explicit size, which extends to the end of the file
     */
    public static function unsizedBox(string $type, string $payload = ''): string
    {
        return pack('N', 0) . $type . $payload;
    }

    /**
     * Encode a box with a version and flags
     */
    public static function fullBox(string $type, int $version, string $payload = '', int $flags = 0): string
    {
        return self::box($type, chr($version) . substr(pack('N', $flags), 1) . $payload);
    }

    /**
     * Use a different major brand
     */
    public function withBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Use different image dimensions
     */
    public function withDimensions(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Use a different color depth, or no depth property at all
     */
    public function withDepth(int $depth): self
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Embed an ICC profile in a color information property
     */
    public function withColorProfile(string $data, string $type = 'prof'): self
    {
        $this->colorProfile = $type . $data;

        return $this;
    }

    /**
     * Embed Exif metadata, preceded by the offset of its TIFF header
     */
    public function withExif(string $tiff, int $headerOffset = 0): self
    {
        $this->exif = pack('N', $headerOffset) . str_repeat("\x00", $headerOffset) . $tiff;

        return $this;
    }

    /**
     * Add an Exif item with the given data, which does not have to be well-formed
     */
    public function withRawExif(string $data): self
    {
        $this->exif = $data;

        return $this;
    }

    /**
     * Store the given extent for an item, whatever the position and the length of its data
     */
    public function withExtentOverride(int $itemId, ?int $offset = null, ?int $length = null): self
    {
        $this->extentOverrides[$itemId] = ['offset' => $offset, 'length' => $length];

        return $this;
    }

    /**
     * Make an item point to the data of another file through a data reference
     */
    public function withDataReference(int $itemId, int $index = 1): self
    {
        $this->dataReferences[$itemId] = $index;

        return $this;
    }

    /**
     * Add a free space box the rewriting has to keep
     */
    public function withFreeSpace(): self
    {
        $this->freeSpace = true;

        return $this;
    }

    /**
     * Leave out the image spatial extents property
     */
    public function withoutDimensions(): self
    {
        $this->dimensions = false;

        return $this;
    }

    /**
     * Add an auxiliary item holding the alpha channel
     */
    public function withAlpha(): self
    {
        $this->alpha = true;

        return $this;
    }

    /**
     * Turn the file into an image sequence, which cannot be rewritten
     */
    public function asSequence(): self
    {
        $this->sequence = true;
        $this->brand = 'avis';

        return $this;
    }

    /**
     * Give the image sequence a timeline of frames, played a number of times (0 to loop forever)
     */
    public function withFrames(int $frames, int $plays = 1): self
    {
        $this->frames = $frames;
        $this->plays = $plays;

        return $this->asSequence();
    }

    /**
     * Describe the codec configuration of the primary item
     */
    public function withCodecConfiguration(bool $monochrome): self
    {
        $this->monochrome = $monochrome;

        return $this;
    }

    /**
     * Use a different version of the item location box, optionally with base offsets
     */
    public function withLocationVersion(int $version, int $baseOffsetSize = 0): self
    {
        $this->locationVersion = $version;
        $this->baseOffsetSize = $baseOffsetSize;

        return $this;
    }

    /**
     * Give the extents of the item location box an explicit index
     */
    public function withExtentIndexes(): self
    {
        $this->extentIndexes = true;

        return $this;
    }

    /**
     * Store the data of the Exif item in the item data box instead of the media data one
     */
    public function withItemDataBox(): self
    {
        $this->itemDataBox = true;

        return $this;
    }

    /**
     * Place the media data box before the metadata
     */
    public function withMediaDataFirst(): self
    {
        $this->mediaDataFirst = true;

        return $this;
    }

    /**
     * Give the media data box a 64-bit size
     */
    public function withLargeMediaDataBox(): self
    {
        $this->largeMediaDataBox = true;

        return $this;
    }

    /**
     * Give the media data box no explicit size, so that it extends to the end of the file
     */
    public function withUnsizedMediaDataBox(): self
    {
        $this->unsizedMediaDataBox = true;
        $this->mediaDataFirst = false;

        return $this;
    }

    /**
     * Leave the primary item undefined
     */
    public function withoutPrimaryItem(): self
    {
        $this->primaryItem = false;

        return $this;
    }

    /**
     * Leave out the item properties box
     */
    public function withoutItemProperties(): self
    {
        $this->itemProperties = false;

        return $this;
    }

    /**
     * Store the property associations as 15-bit indexes
     */
    public function withWideAssociations(): self
    {
        $this->wideAssociations = true;

        return $this;
    }

    /**
     * Add properties not associated with any item, to push the following indexes further
     */
    public function withFillerProperties(int $count): self
    {
        $this->fillerProperties = $count;

        return $this;
    }

    /**
     * Build the file
     */
    public function build(): string
    {
        $items = $this->items();
        $inMediaData = array_filter($items, static fn(array $item): bool => !$item['idat']);

        $mediaData = implode('', array_column($inMediaData, 'data'));
        $header = $this->fileType()
            . ($this->freeSpace ? self::box('free', 'free-space') : '')
            . ($this->sequence ? $this->movie() : '');

        // The item location box has a fixed layout, so the metadata keeps the same size
        // once the placeholder offsets are replaced with the definitive ones
        $placeholder = $this->meta($items, 0);
        $offset = strlen($header) + ($this->mediaDataFirst ? $this->mediaDataHeaderLength() : strlen($placeholder) + $this->mediaDataHeaderLength());

        $meta = $this->meta($items, $offset);
        $mediaDataBox = $this->mediaDataBox($mediaData);

        return $header . ($this->mediaDataFirst ? $mediaDataBox . $meta : $meta . $mediaDataBox);
    }

    /**
     * Get the items of the file, with the offsets of their data relative to the area holding it
     *
     * @return list<array{id: int, type: string, data: string, idat: bool, offset: int}>
     */
    private function items(): array
    {
        $entries = [['id' => self::PRIMARY_ID, 'type' => 'av01', 'data' => self::PRIMARY_DATA, 'idat' => false]];

        if ($this->alpha) {
            $entries[] = ['id' => self::ALPHA_ID, 'type' => 'av01', 'data' => self::ALPHA_DATA, 'idat' => false];
        }

        if ($this->exif !== null) {
            $entries[] = ['id' => self::EXIF_ID, 'type' => 'Exif', 'data' => $this->exif, 'idat' => $this->itemDataBox];
        }

        $offsets = [false => 0, true => 0];
        $items = [];

        foreach ($entries as $entry) {
            $items[] = [...$entry, 'offset' => $offsets[$entry['idat']]];
            $offsets[$entry['idat']] += strlen($entry['data']);
        }

        return $items;
    }

    private function fileType(): string
    {
        return self::box('ftyp', $this->brand . pack('N', 0) . 'avif' . 'mif1' . 'miaf');
    }

    private function mediaDataHeaderLength(): int
    {
        return $this->largeMediaDataBox ? 16 : 8;
    }

    private function mediaDataBox(string $payload): string
    {
        if ($this->largeMediaDataBox) {
            return self::largeBox('mdat', $payload);
        }

        return $this->unsizedMediaDataBox ? self::unsizedBox('mdat', $payload) : self::box('mdat', $payload);
    }

    /**
     * @param list<array{id: int, type: string, data: string, idat: bool, offset: int}> $items
     */
    private function meta(array $items, int $mediaDataOffset): string
    {
        $children = $this->handler()
            . ($this->primaryItem ? self::fullBox('pitm', 0, pack('n', self::PRIMARY_ID)) : '')
            . $this->itemInfo($items)
            . $this->itemLocation($items, $mediaDataOffset)
            . ($this->itemProperties ? $this->properties() : '')
            . $this->references()
            . $this->itemData($items);

        return self::fullBox('meta', 0, $children);
    }

    private function handler(): string
    {
        return self::fullBox('hdlr', 0, pack('N', 0) . 'pict' . str_repeat("\x00", 12) . "\x00");
    }

    /**
     * @param list<array{id: int, type: string, data: string, idat: bool, offset: int}> $items
     */
    private function itemInfo(array $items): string
    {
        $entries = '';

        foreach ($items as $item) {
            $entries .= self::fullBox('infe', 2, pack('nn', $item['id'], 0) . $item['type'] . "\x00");
        }

        return self::fullBox('iinf', 0, pack('n', count($items)) . $entries);
    }

    /**
     * @param list<array{id: int, type: string, data: string, idat: bool, offset: int}> $items
     */
    private function itemLocation(array $items, int $mediaDataOffset): string
    {
        $version = $this->locationVersion;
        $indexSize = $this->extentIndexes ? 4 : 0;
        $wide = $version < 2 ? 2 : 4;

        $payload = pack('n', (4 << 12) | (4 << 8) | ($this->baseOffsetSize << 4) | ($version > 0 ? $indexSize : 0))
            . ($version < 2 ? pack('n', count($items)) : pack('N', count($items)));

        foreach ($items as $item) {
            $method = $item['idat'] ? 1 : 0;
            $base = $item['idat'] ? 0 : $mediaDataOffset;
            $override = $this->extentOverrides[$item['id']] ?? ['offset' => null, 'length' => null];

            $payload .= ($wide === 2 ? pack('n', $item['id']) : pack('N', $item['id']))
                . ($version > 0 ? pack('n', $method) : '')
                . pack('n', $this->dataReferences[$item['id']] ?? 0)
                . ($this->baseOffsetSize > 0 ? substr(pack('J', $base), 8 - $this->baseOffsetSize) : '')
                . pack('n', 1)
                . ($version > 0 && $indexSize > 0 ? pack('N', 1) : '')
                . pack('N', $override['offset'] ?? $item['offset'] + ($this->baseOffsetSize > 0 ? 0 : $base))
                . pack('N', $override['length'] ?? strlen($item['data']));
        }

        return self::fullBox('iloc', $version, $payload);
    }

    private function movie(): string
    {
        if ($this->frames === null) {
            return self::box('moov');
        }

        // Every frame lasts one unit, so the duration of the track is the one of all its plays
        $duration = $this->plays === 0 ? str_repeat("\xff", 8) : pack('J', $this->frames * $this->plays);
        $header = self::fullBox('tkhd', 1, pack('JJ', 0, 0) . pack('NN', 1, 0) . $duration . str_repeat("\x00", 60), 3);
        $edits = self::box('edts', self::fullBox('elst', 1, pack('N', 1) . pack('JJ', $this->frames, 0) . pack('N', 0x10000), $this->plays === 1 ? 0 : 1));
        $sizes = self::fullBox('stsz', 0, pack('NN', 0, $this->frames) . str_repeat(pack('N', 10), $this->frames));
        $media = self::box('mdia', self::box('minf', self::box('stbl', $sizes)));

        return self::box('moov', self::box('trak', $header . $edits . $media));
    }

    private function properties(): string
    {
        $properties = [];
        $associations = [self::PRIMARY_ID => []];

        if ($this->dimensions) {
            $properties[] = self::fullBox('ispe', 0, pack('NN', $this->width, $this->height));
            $associations[self::PRIMARY_ID][] = count($properties);
        }

        if ($this->depth > 0) {
            $properties[] = self::fullBox('pixi', 0, chr(3) . str_repeat(chr($this->depth), 3));
            $associations[self::PRIMARY_ID][] = count($properties);
        }

        if ($this->monochrome !== null) {
            $properties[] = self::box('av1C', "\x81\x00" . chr($this->monochrome ? 0x1c : 0x0c) . "\x00");
            $associations[self::PRIMARY_ID][] = count($properties);
        }

        if ($this->colorProfile !== null) {
            $properties[] = self::box('colr', $this->colorProfile);
            $associations[self::PRIMARY_ID][] = count($properties);
        }

        for ($i = 0; $i < $this->fillerProperties; $i++) {
            $properties[] = self::box('free', 'filler');
        }

        if ($this->alpha) {
            $properties[] = self::fullBox('auxC', 0, self::ALPHA_AUX_TYPE . "\x00");
            $associations[self::ALPHA_ID] = [...($this->dimensions ? [1] : []), count($properties)];
        }

        $container = self::box('ipco', implode('', $properties));

        return self::box('iprp', $container . $this->associations($associations));
    }

    /**
     * @param array<int, list<int>> $associations
     */
    private function associations(array $associations): string
    {
        $wide = $this->wideAssociations;
        $payload = pack('N', count($associations));

        foreach ($associations as $itemId => $indexes) {
            $payload .= pack('n', $itemId) . chr(count($indexes));

            foreach ($indexes as $index) {
                $payload .= $wide ? pack('n', $index) : chr($index);
            }
        }

        return self::fullBox('ipma', 0, $payload, $wide ? 1 : 0);
    }

    private function references(): string
    {
        $references = '';

        if ($this->alpha) {
            $references .= self::box('auxl', pack('nnn', self::ALPHA_ID, 1, self::PRIMARY_ID));
        }

        if ($this->exif !== null) {
            $references .= self::box('cdsc', pack('nnn', self::EXIF_ID, 1, self::PRIMARY_ID));
        }

        return $references === '' ? '' : self::fullBox('iref', 0, $references);
    }

    /**
     * @param list<array{id: int, type: string, data: string, idat: bool, offset: int}> $items
     */
    private function itemData(array $items): string
    {
        $payload = implode('', array_column(array_filter($items, static fn(array $item): bool => $item['idat']), 'data'));

        return $payload === '' ? '' : self::box('idat', $payload);
    }
}
