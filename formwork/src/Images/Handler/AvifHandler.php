<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Handler\Utils\Avif\Box;
use Formwork\Images\Handler\Utils\Avif\ByteReader;
use Formwork\Images\Handler\Utils\Avif\ByteWriter;
use Formwork\Images\Handler\Utils\Avif\ItemInfo;
use Formwork\Images\Handler\Utils\Avif\ItemLocation;
use Formwork\Images\Handler\Utils\Avif\ItemReferences;
use Formwork\Images\Handler\Utils\Avif\PropertyAssociations;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class AvifHandler extends AbstractHandler
{
    /**
     * Types of `colr` boxes containing an ICC profile
     */
    private const array ICC_COLOR_TYPES = ['prof', 'rICC'];

    /**
     * Top-level boxes of the parsed image data
     *
     * @var list<Box>
     */
    private array $boxes = [];

    /**
     * Boxes whose payload is generated from a model, together with the model
     *
     * @var list<array{Box, ItemInfo|ItemLocation|ItemReferences|PropertyAssociations}>
     */
    private array $models = [];

    private ?Box $meta = null;

    private ?Box $properties = null;

    private ?ItemLocation $location = null;

    private ?ItemInfo $items = null;

    private ?ItemReferences $references = null;

    /**
     * @var list<PropertyAssociations>
     */
    private array $associations = [];

    private ?int $primaryItemId = null;

    public function getInfo(): ImageInfo
    {
        $this->parse();

        $info = ['width' => 0, 'height' => 0, 'colorDepth' => 8, 'hasAlphaChannel' => false];

        $properties = array_filter(array_map(fn(int $index) => $this->properties->children[$index - 1] ?? null, $this->primaryPropertyIndexes()));

        foreach ($properties ?: $this->properties->children ?? [] as $property) {
            if ($property->type === 'ispe') {
                $reader = $property->fullBoxReader();
                $info['width'] = $reader->uint(4);
                $info['height'] = $reader->uint(4);
            } elseif ($property->type === 'pixi' && ($reader = $property->fullBoxReader())->uint(1) > 0) {
                $info['colorDepth'] = $reader->uint(1);
            }
        }

        foreach ($this->properties?->childrenOfType('auxC') ?? [] as $property) {
            $info['hasAlphaChannel'] = $info['hasAlphaChannel'] || str_contains($property->fullBoxReader()->string(), 'alpha');
        }

        return new ImageInfo([
            'mimeType'             => 'image/avif',
            'colorSpace'           => ColorSpace::RGB,
            'colorNumber'          => null,
            'isAnimation'          => $this->isAnimation(),
            'animationFrames'      => null,
            'animationRepeatCount' => null,
            ...$info,
        ]);
    }

    public function supportsTransforms(): bool
    {
        $this->parse();

        return !$this->isAnimation();
    }

    public static function supportsColorProfile(): bool
    {
        return true;
    }

    public function hasColorProfile(): bool
    {
        $this->parse();

        return $this->findIccPropertyIndex() !== null;
    }

    public function getColorProfile(): ?ColorProfile
    {
        $this->parse();

        $index = $this->findIccPropertyIndex();

        return $index === null ? null : new ColorProfile(substr($this->getProperties()->children[$index - 1]->body, 4));
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $this->parse();
        $this->assertEditable();

        $properties = $this->getProperties();
        $colr = new Box('colr', 'prof' . $colorProfile->getData());

        if (($index = $this->findIccPropertyIndex()) !== null) {
            array_splice($properties->children, $index - 1, 1, [$colr]);
        } else {
            // Properties are applied to an item only if associated with it
            $properties->children[] = $colr;
            $this->getAssociations()->associate($this->getPrimaryItemId(), count($properties->children));
        }

        $this->data = $this->serialize();
    }

    public function removeColorProfile(): void
    {
        $this->parse();

        if (($index = $this->findIccPropertyIndex()) === null) {
            return;
        }

        $this->assertEditable();

        // Associations refer to properties by index, so they must follow the removal
        array_splice($this->getProperties()->children, $index - 1, 1);

        foreach ($this->associations as $associations) {
            $associations->removeProperty($index);
        }

        $this->data = $this->serialize();
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        return $this->getExifData() !== null;
    }

    public function getExifData(): ?ExifData
    {
        $this->parse();

        if (($itemId = $this->items?->findByType('Exif')) === null || ($payload = $this->readItem($itemId)) === null) {
            return null;
        }

        // The payload starts with the offset of the TIFF header, which may be preceded by other data
        $tiffHeaderOffset = (new ByteReader($payload))->uint(4);

        return new ExifData(substr($payload, 4 + $tiffHeaderOffset));
    }

    public function setExifData(ExifData $exifData): void
    {
        $this->parse();
        $this->assertEditable();
        $this->removeExifItems();

        $payload = (new ByteWriter())->uint(0, 4)->bytes($exifData->getData())->toString();
        $itemId = $this->getItems()->maxId() + 1;
        $mdat = $this->getDataBox();

        $this->getLocation()->add($itemId, $mdat, strlen($mdat->body), strlen($payload));
        $mdat->body .= $payload;

        $this->getItems()->add($itemId, 'Exif');
        $this->getReferences()->add('cdsc', $itemId, $this->getPrimaryItemId());

        $this->data = $this->serialize();
    }

    public function removeExifData(): void
    {
        $this->parse();

        if ($this->items?->findByType('Exif') === null) {
            return;
        }

        $this->assertEditable();
        $this->removeExifItems();

        $this->data = $this->serialize();
    }

    protected function getDecoder(): AvifDecoder
    {
        return new AvifDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        imagesavealpha($gdImage, true);

        if (!imageistruecolor($gdImage)) {
            imagepalettetotruecolor($gdImage);
        }

        ob_start();

        if (imageavif($gdImage, null, $this->options['avifQuality']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }
        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');
    }

    /**
     * Parse the boxes of the image data
     */
    private function parse(): void
    {
        $this->boxes = [];
        $this->models = [];
        $this->properties = null;
        $this->location = null;
        $this->items = null;
        $this->references = null;
        $this->associations = [];
        $this->primaryItemId = null;

        foreach ($this->decoder->decode($this->data) as $decoded) {
            $this->boxes[] = Box::fromDecoded($decoded);
        }

        $this->meta = $this->topLevelBox('meta');

        if ($this->meta === null) {
            return;
        }

        $properties = $this->meta->child('iprp');

        $this->properties = $properties?->child('ipco');

        foreach ($properties?->childrenOfType('ipma') ?? [] as $box) {
            $this->associations[] = $this->track($box, PropertyAssociations::parse($box->body));
        }

        if (($box = $this->meta->child('iloc')) !== null) {
            $this->location = $this->track($box, ItemLocation::parse($box->body));
            $this->location->bind($this->boxes);
        }

        if (($box = $this->meta->child('iinf')) !== null) {
            $this->items = $this->track($box, ItemInfo::parse($box));
        }

        if (($box = $this->meta->child('iref')) !== null) {
            $this->references = $this->track($box, ItemReferences::parse($box));
        }

        if (($box = $this->meta->child('pitm')) !== null) {
            $this->primaryItemId = $box->fullBoxReader()->uint(($box->body[0] ?? "\0") === "\0" ? 2 : 4);
        }
    }

    /**
     * Generate the image data from the boxes
     */
    private function serialize(): string
    {
        // The size of the boxes does not depend on the offsets stored in the `iloc` box, so the new layout
        // of the file can be computed first and the offsets updated afterwards
        $this->updateModels();

        $bodyOffsets = [];
        $position = 0;

        foreach ($this->boxes as $box) {
            $bodyOffsets[spl_object_id($box)] = $position + $box->headerSize();
            $position += $box->size();
        }

        $this->location?->relocate(static fn(Box $box) => $bodyOffsets[spl_object_id($box)]);
        $this->updateModels();

        return implode('', array_map(static fn(Box $box) => $box->serialize(), $this->boxes));
    }

    private function isAnimation(): bool
    {
        return str_starts_with($this->topLevelBox('ftyp')->body ?? '', 'avis');
    }

    private function removeExifItems(): void
    {
        while (($itemId = $this->items?->findByType('Exif')) !== null) {
            $this->removeExifItem($itemId);
        }
    }

    private function topLevelBox(string $type): ?Box
    {
        foreach ($this->boxes as $box) {
            if ($box->type === $type) {
                return $box;
            }
        }

        return null;
    }

    /**
     * @template T of ItemLocation|ItemInfo|ItemReferences|PropertyAssociations
     *
     * @param T $model
     *
     * @return T
     */
    private function track(Box $box, object $model): object
    {
        $this->models[] = [$box, $model];

        return $model;
    }

    private function updateModels(): void
    {
        foreach ($this->models as [$box, $model]) {
            $model->writeTo($box);
        }
    }

    /**
     * Get the indexes of the properties of the primary item
     *
     * @return list<int>
     */
    private function primaryPropertyIndexes(): array
    {
        if ($this->primaryItemId === null) {
            return [];
        }

        $primaryItemId = $this->primaryItemId;

        return array_merge(...array_map(static fn(PropertyAssociations $associations) => $associations->propertiesOf($primaryItemId), $this->associations));
    }

    private function findIccPropertyIndex(): ?int
    {
        foreach ($this->primaryPropertyIndexes() as $index) {
            $property = $this->properties->children[$index - 1] ?? null;

            if ($property?->type === 'colr' && in_array(substr($property->body, 0, 4), self::ICC_COLOR_TYPES, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Read the data of an item stored in the file
     */
    private function readItem(int $itemId): ?string
    {
        $extents = $this->location?->extents($itemId);

        if ($extents === null || $this->location->method($itemId) !== 0) {
            return null;
        }

        $data = '';

        foreach ($extents as $extent) {
            if ($extent->box === null) {
                return null;
            }

            $data .= substr($extent->box->body, $extent->anchor, $extent->length);
        }

        return $data;
    }

    /**
     * Remove an item, together with its data and references
     */
    private function removeExifItem(int $itemId): void
    {
        $location = $this->getLocation();
        $extents = $location->extents($itemId) ?? [];

        if ($extents !== [] && $location->method($itemId) !== 0) {
            throw new UnsupportedFeatureException('Removing items not stored in the file is not supported');
        }

        foreach ($extents as $extent) {
            if ($extent->box !== null) {
                $extent->box->body = substr_replace($extent->box->body, '', $extent->anchor, $extent->length);
                $location->shrink($extent->box, $extent->anchor, $extent->length);
            }
        }

        $location->remove($itemId);
        $this->getItems()->remove($itemId);
        $this->references?->remove($itemId);

        foreach ($this->associations as $associations) {
            $associations->removeItem($itemId);
        }
    }

    /**
     * @throws UnsupportedFeatureException If the file cannot be safely modified
     */
    private function assertEditable(): void
    {
        if ($this->topLevelBox('moov') !== null) {
            throw new UnsupportedFeatureException('Editing the metadata of AVIF image sequences is not supported');
        }

        if ($this->location?->isRelocatable() === false) {
            throw new UnsupportedFeatureException('AVIF file contains item data that cannot be relocated');
        }
    }

    private function getProperties(): Box
    {
        return $this->properties ?? throw new UnsupportedFeatureException('AVIF file does not contain an ipco box');
    }

    private function getAssociations(): PropertyAssociations
    {
        return $this->associations[0] ?? throw new UnsupportedFeatureException('AVIF file does not contain an ipma box');
    }

    private function getLocation(): ItemLocation
    {
        return $this->location ?? throw new UnsupportedFeatureException('AVIF file does not contain an iloc box');
    }

    private function getItems(): ItemInfo
    {
        return $this->items ?? throw new UnsupportedFeatureException('AVIF file does not contain an iinf box');
    }

    private function getPrimaryItemId(): int
    {
        return $this->primaryItemId ?? throw new UnsupportedFeatureException('AVIF file does not contain a pitm box');
    }

    /**
     * Get the item references, adding an `iref` box if not present
     */
    private function getReferences(): ItemReferences
    {
        if ($this->references === null) {
            $meta = $this->meta ?? throw new UnsupportedFeatureException('AVIF file does not contain a meta box');
            $box = new Box('iref');

            $position = array_search($meta->child('iinf'), $meta->children, true);
            array_splice($meta->children, $position === false ? count($meta->children) : $position + 1, 0, [$box]);

            $this->references = $this->track($box, new ItemReferences());
        }

        return $this->references;
    }

    /**
     * Get the box where the data of new items is stored, adding an `mdat` box if not present
     */
    private function getDataBox(): Box
    {
        foreach (array_reverse($this->boxes) as $box) {
            if ($box->type === 'mdat') {
                return $box;
            }
        }

        return $this->boxes[] = new Box('mdat');
    }
}
