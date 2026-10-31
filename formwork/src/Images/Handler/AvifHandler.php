<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\Container;
use Formwork\Images\Isobmff\ItemInfoEntry;
use Formwork\Images\Isobmff\ItemPropertiesBox;
use Formwork\Images\Isobmff\ItemReferenceBox;
use Formwork\Images\Isobmff\MetaBox;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class AvifHandler extends AbstractHandler
{
    /**
     * Major brand of AVIF image sequences
     */
    private const string SEQUENCE_BRAND = 'avis';

    /**
     * Color information types holding an ICC profile (unrestricted and restricted)
     */
    private const array ICC_COLOR_TYPES = ['prof', 'rICC'];

    /**
     * Type used when writing ICC profiles, since it is not restricted to simple profiles
     */
    private const string ICC_WRITE_TYPE = 'prof';

    /**
     * Size of the field storing the offset of the TIFF header in Exif items
     */
    private const int EXIF_OFFSET_SIZE = 4;

    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/avif',
            'width'                => 0,
            'height'               => 0,
            'colorSpace'           => ColorSpace::RGB,
            'colorDepth'           => 8,
            'colorNumber'          => null,
            'hasAlphaChannel'      => false,
            'isAnimation'          => false,
            'animationFrames'      => null,
            'animationRepeatCount' => null,
        ];

        $container = $this->container();
        $meta = $container->meta();

        if ($container->majorBrand() === self::SEQUENCE_BRAND) {
            $info['isAnimation'] = true;
        }

        if ($meta !== null) {
            $info = [...$info, ...self::describeProperties($meta)];
        }

        $sequence = $container->imageSequence();

        if ($sequence !== null) {
            $info['animationFrames'] = $sequence->frameCount;
            $info['animationRepeatCount'] = $sequence->playCount;
        }

        return new ImageInfo($info);
    }

    public function supportsTransforms(): bool
    {
        return !$this->getInfo()->isAnimation();
    }

    public static function supportsColorProfile(): bool
    {
        return true;
    }

    public function hasColorProfile(): bool
    {
        return $this->findColorProfileData() !== null;
    }

    public function getColorProfile(): ?ColorProfile
    {
        $data = $this->findColorProfileData();

        return $data === null ? null : new ColorProfile($data);
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $container = $this->container();
        $meta = $container->requireMeta();
        $properties = $meta->require(ItemPropertiesBox::class);

        $properties->removeWhere(self::isColorProfile(...));
        $properties->add(new Box('colr', self::ICC_WRITE_TYPE . $colorProfile->getData()), $meta->requirePrimaryItemId());

        $this->data = $container->toString();
    }

    public function removeColorProfile(): void
    {
        $container = $this->container();

        if ($container->meta()?->find(ItemPropertiesBox::class)?->removeWhere(self::isColorProfile(...))) {
            $this->data = $container->toString();
        }
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        return $this->container()->itemIdsByType(ItemInfoEntry::EXIF_TYPE) !== [];
    }

    public function getExifData(): ?ExifData
    {
        $container = $this->container();
        $id = $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE)[0] ?? null;
        $item = $id === null ? null : $container->itemData($id);
        $tiff = $item === null ? null : self::extractTiffData($item);

        return $tiff === null ? null : new ExifData($tiff);
    }

    public function setExifData(ExifData $exifData): void
    {
        $container = $this->container();
        $primaryId = $container->requireMeta()->requirePrimaryItemId();

        self::removeExifItems($container);

        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, str_repeat("\x00", self::EXIF_OFFSET_SIZE) . $exifData->getData());
        $container->addReference(ItemReferenceBox::CONTENT_DESCRIBES, $id, $primaryId);

        $this->data = $container->toString();
    }

    public function removeExifData(): void
    {
        $container = $this->container();

        if (self::removeExifItems($container) > 0) {
            $this->data = $container->toString();
        }
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
        $success = imageavif($gdImage, null, $this->options['avifQuality']);
        $data = (string) ob_get_clean();

        if ($success === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = $data !== '' ? $data : throw new UnexpectedValueException('Unexpected empty image data');
    }

    /**
     * Get the info stored in the properties of the image
     *
     * @return array<string, mixed>
     */
    private static function describeProperties(MetaBox $meta): array
    {
        $properties = $meta->find(ItemPropertiesBox::class);

        if ($properties === null) {
            return [];
        }

        $primaryId = $meta->primaryItemId();
        $primary = ($primaryId === null ? [] : $properties->forItem($primaryId)) ?: $properties->all();

        return [
            ...self::describeDimensions(self::firstOfType($primary, 'ispe')),
            ...self::describeDepth(self::firstOfType($primary, 'pixi')),
            ...self::describeColorSpace(self::firstOfType($primary, 'av1C')),
            'hasAlphaChannel' => self::hasAlphaChannel($properties->all()),
        ];
    }

    /**
     * @param list<Box> $properties
     */
    private static function firstOfType(array $properties, string $type): ?Box
    {
        foreach ($properties as $property) {
            if ($property->type === $type) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @return array{width?: int, height?: int}
     */
    private static function describeDimensions(?Box $ispe): array
    {
        if ($ispe === null) {
            return [];
        }

        $reader = new ByteReader($ispe->payload);
        $reader->skip(4);

        return ['width' => $reader->uint(4), 'height' => $reader->uint(4)];
    }

    /**
     * @return array{colorDepth?: int}
     */
    private static function describeDepth(?Box $pixi): array
    {
        if ($pixi === null) {
            return [];
        }

        $reader = new ByteReader($pixi->payload);
        $reader->skip(4);

        return $reader->uint(1) > 0 ? ['colorDepth' => $reader->uint(1)] : [];
    }

    /**
     * @return array{colorSpace?: ColorSpace}
     */
    private static function describeColorSpace(?Box $av1C): array
    {
        // The monochrome flag is the fifth bit of the third byte of the codec configuration
        return $av1C !== null && strlen($av1C->payload) > 2 && (ord($av1C->payload[2]) & 0x10) !== 0
            ? ['colorSpace' => ColorSpace::Grayscale]
            : [];
    }

    /**
     * @param list<Box> $properties
     */
    private static function hasAlphaChannel(array $properties): bool
    {
        foreach ($properties as $property) {
            if ($property->type === 'auxC' && str_contains(explode("\x00", substr($property->payload, 4), 2)[0], 'alpha')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return whether a property is an embedded ICC profile
     */
    private static function isColorProfile(Box $property): bool
    {
        return $property->type === 'colr' && in_array(substr($property->payload, 0, 4), self::ICC_COLOR_TYPES, true);
    }

    /**
     * Remove all Exif items
     *
     * @return int Number of removed items
     */
    private static function removeExifItems(Container $container): int
    {
        $ids = $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE);

        foreach ($ids as $id) {
            $container->removeItem($id);
        }

        return count($ids);
    }

    /**
     * Get the TIFF data of an Exif item, which is preceded by the offset of its header
     */
    private static function extractTiffData(string $item): ?string
    {
        if (strlen($item) < self::EXIF_OFFSET_SIZE) {
            return null;
        }

        $tiff = substr($item, self::EXIF_OFFSET_SIZE + (new ByteReader($item))->uint(self::EXIF_OFFSET_SIZE));

        return $tiff === '' ? null : $tiff;
    }

    private function container(): Container
    {
        return Container::parse($this->decoder, $this->data);
    }

    /**
     * Get the data of the first embedded ICC profile
     */
    private function findColorProfileData(): ?string
    {
        $properties = $this->container()->meta()?->find(ItemPropertiesBox::class);

        foreach ($properties?->all() ?? [] as $property) {
            if (self::isColorProfile($property)) {
                return substr($property->payload, 4);
            }
        }

        return null;
    }
}
