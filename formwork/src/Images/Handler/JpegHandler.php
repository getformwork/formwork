<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\JpegDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

class JpegHandler extends AbstractHandler
{
    protected const MAX_BYTES_IN_SEGMENT = 65533;

    protected const EXIF_HEADER = "Exif\x00\x00";

    protected const ICC_PROFILE_HEADER = "ICC_PROFILE\x00";

    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/jpeg',
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

        foreach ($this->decoder->decode($this->data) as $segment) {
            if (
                $segment['type'] > 0xbf && $segment['type'] < 0xc3
                || $segment['type'] > 0xc8 && $segment['type'] < 0xcc
            ) {
                $info['colorDepth'] = ord($segment['value'][0]);
                $info['height'] = unpack('n', $segment['value'], 1)[1];
                $info['width'] = unpack('n', $segment['value'], 3)[1];
                $info['colorSpace'] = $this->getColorSpace(ord($segment['value'][5]));
                break;
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
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] !== 0xe2) {
                continue;
            }
            if (!str_starts_with($segment['value'], self::ICC_PROFILE_HEADER)) {
                continue;
            }
            return true;
        }

        return false;
    }

    public function getColorProfile(): ?ColorProfile
    {
        $headerLength = strlen(self::ICC_PROFILE_HEADER);
        $profileChunks = [];
        $chunkCount = 0;

        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xe2 && str_starts_with($segment['value'], self::ICC_PROFILE_HEADER)) {
                [$chunkNum, $chunkCount] = array_values(unpack('Cnum/Ccount', $segment['value'], $headerLength));
                $profileChunks[$chunkNum] = substr($segment['value'], $headerLength + 2);
            }
        }

        if ($profileChunks === []) {
            return null;
        }

        if (count($profileChunks) !== $chunkCount) {
            throw new UnexpectedValueException('Unexpected profile chunk count');
        }

        ksort($profileChunks);
        return new ColorProfile(implode('', $profileChunks));
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xd8) {
                $this->data = substr_replace($this->data, $this->encodeColorProfile($colorProfile->getData()), $segment['position'], 0);
                break;
            }
        }
    }

    public function removeColorProfile(): void
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xe2 && str_starts_with($segment['value'], self::ICC_PROFILE_HEADER)) {
                $this->data = substr_replace($this->data, '', $segment['offset'], $segment['position'] - $segment['offset']);
                $segment['position'] = $segment['offset'];
            }
        }
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] !== 0xe1) {
                continue;
            }
            if (!str_starts_with($segment['value'], self::EXIF_HEADER)) {
                continue;
            }
            return true;
        }
        return false;
    }

    public function getExifData(): ?ExifData
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] !== 0xe1) {
                continue;
            }
            if (!str_starts_with($segment['value'], self::EXIF_HEADER)) {
                continue;
            }
            return new ExifData(substr($segment['value'], strlen(self::EXIF_HEADER)));
        }
        return null;
    }

    public function setExifData(ExifData $exifData): void
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xd8) {
                $this->data = substr_replace($this->data, $this->encodeExifData($exifData->getData()), $segment['position'], 0);
                break;
            }
        }
    }

    public function removeExifData(): void
    {
        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xe1 && str_starts_with($segment['value'], self::EXIF_HEADER)) {
                $this->data = substr_replace($this->data, '', $segment['offset'], $segment['position'] - $segment['offset']);
                $segment['position'] = $segment['offset'];
            }
        }
    }

    protected function getColorSpace(int $components): ColorSpace
    {
        return match ($components) {
            1       => ColorSpace::Grayscale,
            3       => ColorSpace::RGB,
            4       => ColorSpace::CMYK,
            default => throw new UnexpectedValueException('Invalid color space'),
        };
    }

    protected function encodeColorProfile(string $data): string
    {
        $maxChunkSize = self::MAX_BYTES_IN_SEGMENT - strlen(self::ICC_PROFILE_HEADER) - 4;
        $chunks = str_split($data, $maxChunkSize);
        $count = count($chunks);

        for ($i = 0; $i < $count; $i++) {
            $value = self::ICC_PROFILE_HEADER . pack('CC', $i + 1, $count) . $chunks[$i];
            $chunks[$i] = "\xff\xe2" . pack('n', strlen($value) + 2) . $value;
        }

        return implode('', $chunks);
    }

    protected function encodeExifData(string $data): string
    {
        $value = self::EXIF_HEADER . $data;
        return "\xff\xe1" . pack('n', strlen($value) + 2) . $value;
    }

    protected function getDecoder(): JpegDecoder
    {
        return new JpegDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        imageinterlace($gdImage, $this->options['jpegProgressive']);

        ob_start();

        if (imagejpeg($gdImage, null, $this->options['jpegQuality']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean();
    }
}
