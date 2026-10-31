<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Binary\Endianness;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\GifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

final class GifHandler extends AbstractHandler
{
    /**
     * Netscape GIF extension header
     */
    private const string NETSCAPE_EXT_HEADER = "!\xff\x0bNETSCAPE2.0";

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

        $frames = 0;

        foreach ($this->decoder->decode($this->data) as $block) {
            if ($block['type'] === 'LSD') {
                $info['width'] = $block['desc']['width'];
                $info['height'] = $block['desc']['height'];

                if ($block['desc']['gctflag'] === 1) {
                    $info['colorNumber'] = 2 ** ($block['desc']['gctsize'] + 1);
                }
            }

            if ($block['type'] === 'EXT' && $block['label'] === 0xf9) {
                // The transparency flag is the lowest bit of the packed field of the graphic control extension
                if ($info['hasAlphaChannel'] === false) {
                    $info['hasAlphaChannel'] = (Bytes::unpack($block['value'], 3, 1) & 0x01) === 1;
                }
            }

            if ($block['type'] === 'EXT' && str_starts_with($block['value'], self::NETSCAPE_EXT_HEADER)) {
                $info['animationRepeatCount'] = Bytes::unpack($block['value'], 16, 2, Endianness::Little);
                if ($info['animationRepeatCount'] > 0) {
                    $info['animationRepeatCount']++;
                }
            }

            if ($block['type'] === 'IMG') {
                $frames++;
            }
        }

        // An image is animated only if it has more than one frame
        if ($frames > 1) {
            $info['isAnimation'] = true;
            $info['animationFrames'] = $frames;
        } else {
            $info['animationRepeatCount'] = null;
        }

        return new ImageInfo($info);
    }

    public function supportsTransforms(): bool
    {
        return !$this->getInfo()->isAnimation();
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

    public function setColorProfile(ColorProfile $colorProfile): void
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

    public function setExifData(ExifData $exifData): void
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

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        // We need to copy the original image resampled to a new image to avoid transparency issues

        $width = imagesx($gdImage);
        $height = imagesy($gdImage);

        if (($image = imagecreatetruecolor($width, $height)) === false) {
            throw new RuntimeException('Cannot create GIF image from GdImage');
        }

        if (($transparent = imagecolorallocatealpha($image, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate transparent color');
        }

        imagecolortransparent($image, $transparent);

        imagefill($image, 0, 0, $transparent);

        imagecopyresampled($image, $gdImage, 0, 0, 0, 0, $width, $height, $width, $height);

        imagetruecolortopalette($image, true, $this->options['gifColors']);

        ob_start();

        if (imagegif($image) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');
    }
}
