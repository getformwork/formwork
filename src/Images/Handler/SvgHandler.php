<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\SvgDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\ImageInfo;
use GdImage;

final class SvgHandler extends AbstractHandler
{
    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/svg+xml',
            'width'                => 0,
            'height'               => 0,
            'colorSpace'           => ColorSpace::RGB,
            'colorDepth'           => 8,
            'colorNumber'          => null,
            'hasAlphaChannel'      => true,
            'isAnimation'          => false,
            'animationFrames'      => null,
            'animationRepeatCount' => null,
        ];

        foreach ($this->decoder->decode($this->data) as $dimensions) {
            $info['width'] = (int) $dimensions['width'];
            $info['height'] = (int) $dimensions['height'];
        }

        return new ImageInfo($info);
    }

    public function supportsTransforms(): bool
    {
        return false;
    }

    public static function supportsColorProfile(): bool
    {
        return false;
    }

    public function hasColorProfile(): bool
    {
        return false;
    }

    public function getColorProfile(): ?ColorProfile
    {
        throw new UnsupportedFeatureException('SVG does not support color profiles');
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        throw new UnsupportedFeatureException('SVG does not support color profiles');
    }

    public function removeColorProfile(): void
    {
        throw new UnsupportedFeatureException('SVG does not support color profiles');
    }

    public static function supportsExifData(): bool
    {
        return false;
    }

    public function hasExifData(): bool
    {
        return false;
    }

    public function getExifData(): ?ExifData
    {
        throw new UnsupportedFeatureException('SVG does not support EXIF data');
    }

    public function setExifData(ExifData $exifData): void
    {
        throw new UnsupportedFeatureException('SVG does not support EXIF data');
    }

    public function removeExifData(): void
    {
        throw new UnsupportedFeatureException('SVG does not support EXIF data');
    }

    protected function getDecoder(): SvgDecoder
    {
        return new SvgDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        throw new UnsupportedFeatureException('SVG does not support GdImage data');
    }
}
