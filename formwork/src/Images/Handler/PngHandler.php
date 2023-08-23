<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\PngDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

class PngHandler extends AbstractHandler
{
    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/png',
            'width'                => 0,
            'height'               => 0,
            'colorSpace'           => null,
            'colorDepth'           => null,
            'colorNumber'          => null,
            'hasAlphaChannel'      => false,
            'isAnimation'          => false,
            'animationFrames'      => null,
            'animationRepeatCount' => null,
        ];

        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'IHDR') {
                $info['width'] = unpack('N', $chunk['value'], 0)[1];
                $info['height'] = unpack('N', $chunk['value'], 4)[1];
                $info['colorDepth'] = ord($chunk['value'][8]);
                [$info['colorSpace'], $info['hasAlphaChannel']] = $this->getColorSpaceAndAlpha(ord($chunk['value'][9]));
            }

            if ($chunk['type'] === 'PLTE') {
                if ($chunk['size'] % 3 > 0) {
                    throw new UnexpectedValueException('Invalid palette size');
                }
                $info['colorNumber'] = $chunk['size'] / 3;
            }

            if ($chunk['type'] === 'acTL') {
                $info['isAnimation'] = true;
                $info['animationFrames'] = unpack('N', $chunk['value'], 0)[1];
                $info['animationRepeatCount'] = unpack('N', $chunk['value'], 4)[1];
            }
        }

        return new ImageInfo($info);
    }

    public static function supportsColorProfile(): bool
    {
        return true;
    }

    public function hasColorProfile(): bool
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'iCCP') {
                return true;
            }
        }

        return false;
    }

    public function getColorProfile(): ?ColorProfile
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'iCCP') {
                $profile = $this->decodeProfile($chunk['value']);
                return new ColorProfile($profile['value']);
            }
        }

        return null;
    }

    public function setColorProfile(ColorProfile $profile): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'IHDR') {
                $iCCPChunk = $this->encodeChunk('iCCP', $this->encodeProfile($profile->name(), $profile->getData()));
                $this->data = substr_replace($this->data, $iCCPChunk, $chunk['position'], 0);
                break;
            }
        }
    }

    public function removeColorProfile(): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'iCCP') {
                $this->data = substr_replace($this->data, '', $chunk['offset'], $chunk['position'] - $chunk['offset']);
                $chunk['position'] = $chunk['offset'];
                break;
            }
        }
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'eXIf') {
                return true;
            }
        }

        return false;
    }

    public function getExifData(): ?ExifData
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'eXIf') {
                return new ExifData($chunk['value']);
            }
        }

        return null;
    }

    public function setExifData(ExifData $data): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'IHDR') {
                $iCCPChunk = $this->encodeChunk('eXIf', $data->getData());
                $this->data = substr_replace($this->data, $iCCPChunk, $chunk['position'], 0);
                break;
            }
        }
    }

    public function removeExifData(): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'eXIf') {
                $this->data = substr_replace($this->data, '', $chunk['offset'], $chunk['position'] - $chunk['offset']);
                $chunk['position'] = $chunk['offset'];
                break;
            }
        }
    }

    protected function getColorSpaceAndAlpha(int $colorType): array
    {
        return match ($colorType) {
            0       => [ColorSpace::Grayscale, false],
            2       => [ColorSpace::RGB, false],
            3       => [ColorSpace::Palette, false],
            4       => [ColorSpace::Grayscale, true],
            6       => [ColorSpace::RGB, true],
            default => throw new UnexpectedValueException('Invalid color space'),
        };
    }

    protected function encodeChunk(string $name, string $data): string
    {
        return pack('N', strlen($data)) . $name . $data . pack('N', crc32($name . $data));
    }

    protected function decodeProfile(string $data): array
    {
        $name = unpack('Z*', $data)[1];
        $value = gzuncompress(substr($data, strlen($name) + 2));
        return ['name' => $name, 'value' => $value];
    }

    protected function encodeProfile(string $name, string $value): string
    {
        return trim($name) . "\x0\x0" . gzcompress($value);
    }

    protected function getDecoder(): PngDecoder
    {
        return new PngDecoder();
    }

    protected function setDataFromGdImage(GdImage $image): void
    {
        ob_start();

        if (imagepng($image, null, $this->options['pngCompression']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean();
    }
}
