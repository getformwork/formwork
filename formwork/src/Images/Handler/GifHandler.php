<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\GifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;

class GifHandler extends AbstractHandler
{
    protected const NETSCAPE_EXT_HEADER = "!\xff\x0bNETSCAPE2.0";

    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/gif',
            'width'                => 0,
            'height'               => 0,
            'colorSpace'           => ColorSpace::Palette,
            'colorDepth'           => 8,
            'colorNumber'          => null,
            'hasAlphaChannel'      => false,
            'isAnimation'          => false,
            'animationFrames'      => null,
            'animationRepeatCount' => null,
        ];

        foreach ($this->decoder->decode($this->data) as $block) {
            if ($block['type'] === 'LSD') {
                $info['width'] = $block['desc']['width'];
                $info['height'] = $block['desc']['height'];
                $info['colorNumber'] = 2 ** ($block['desc']['colorres'] + 1);
            }

            if ($block['type'] === 'EXT' && $block['label'] === 0xf9) {
                $info['hasAlphaChannel'] = ord($block['value'][3]) & 0x01 === 1;
                if (!$info['isAnimation']) {
                    $info['isAnimation'] = unpack('v', $block['value'], 4)[1] > 0;
                }
            }

            if ($block['type'] === 'EXT' && str_starts_with($block['value'], self::NETSCAPE_EXT_HEADER)) {
                $info['animationRepeatCount'] = unpack('v', $block['value'], 16)[1];
                if ($info['animationRepeatCount'] > 0) {
                    $info['animationRepeatCount']++;
                }
            }

            if ($block['type'] === 'IMG' && $info['isAnimation']) {
                $info['animationFrames']++;
            }
        }

        return new ImageInfo($info);
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
        throw new UnsupportedFeatureException('GIF does not support color profiles');
    }

    public function setColorProfile(ColorProfile $profile): void
    {
        throw new UnsupportedFeatureException('GIF does not support color profiles');
    }

    public function removeColorProfile(): void
    {
        throw new UnsupportedFeatureException('GIF does not support color profiles');
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
        throw new UnsupportedFeatureException('GIF does not support EXIF data');
    }

    public function setExifData(ExifData $data): void
    {
        throw new UnsupportedFeatureException('GIF does not support EXIF data');
    }

    public function removeExifData(): void
    {
        throw new UnsupportedFeatureException('GIF does not support EXIF data');
    }

    protected function getDecoder(): GifDecoder
    {
        return new GifDecoder();
    }

    protected function setDataFromGdImage(GdImage $image): void
    {
        imagetruecolortopalette($image, true, $this->options['gifColors']);

        ob_start();

        if (imagegif($image, null) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean();
    }
}
