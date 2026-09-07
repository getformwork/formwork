<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\WebpDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class WebpHandler extends AbstractHandler
{
    /**
     * RIFF header
     */
    private const string RIFF_HEADER = 'RIFF';

    /**
     * VP8X no flags
     */
    private const int NO_FLAG = 0b00000000;

    /**
     * VP8X alpha flag
     */
    private const int ALPHA_FLAG = 0b00010000;

    /**
     * VP8X ICC flag
     */
    private const int ICC_FLAG = 0b00100000;

    /**
     * VP8X EXIF flag
     */
    private const int EXIF_FLAG = 0b00001000;

    public function getInfo(): ImageInfo
    {
        $info = [
            'mimeType'             => 'image/webp',
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

        $isVP8ChunkParsed = false;

        foreach ($this->decoder->decode($this->data) as $chunk) {
            if (!$isVP8ChunkParsed && $chunk['type'] === 'VP8X') {
                $info['hasAlphaChannel'] = ((ord($chunk['value'][0]) >> 4) & 0x01) === 1;
                $info['width'] = $this->unpack('V', substr($chunk['value'], 4, 3) . "\x00")[1] + 1;
                $info['height'] = $this->unpack('V', substr($chunk['value'], 7, 3) . "\x00")[1] + 1;
                $isVP8ChunkParsed = true;
            }

            if (!$isVP8ChunkParsed && $chunk['type'] === 'VP8 ') {
                $info['width'] = $this->unpack('v', $chunk['value'], 6)[1] & 0x3fff;
                $info['height'] = $this->unpack('v', $chunk['value'], 8)[1] & 0x3fff;
                $isVP8ChunkParsed = true;
            }

            if (!$isVP8ChunkParsed && $chunk['type'] === 'VP8L') {
                $bits = $this->unpack('V', $chunk['value'], 1)[1];
                $info['width'] = ($bits & 0x3fff) + 1;
                $info['height'] = (($bits >> 14) & 0x3fff) + 1;
                $info['hasAlphaChannel'] = (($bits >> 28) & 0x01) === 1;
                $isVP8ChunkParsed = true;
            }

            if ($info['hasAlphaChannel'] === false && $chunk['type'] === 'ALPH') {
                $info['hasAlphaChannel'] = true;
            }

            if ($chunk['type'] === 'ANIM') {
                $info['isAnimation'] = true;
                $info['animationRepeatCount'] = $this->unpack('v', $chunk['value'], 4)[1];
            }
            if (!$info['isAnimation']) {
                continue;
            }
            if ($chunk['type'] !== 'ANMF') {
                continue;
            }
            $info['animationFrames']++;
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
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'ICCP') {
                return true;
            }
        }

        return false;
    }

    public function getColorProfile(): ?ColorProfile
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'ICCP') {
                return new ColorProfile($chunk['value']);
            }
        }

        return null;
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'VP8X') {
                $VP8XFlags = ord($chunk['value'][0]) | self::ICC_FLAG;
                $this->data = substr_replace($this->data, chr($VP8XFlags), $chunk['offset'] + 8, 1);
                $ICCPChunk = $this->encodeChunk('ICCP', $colorProfile->getData());
                $this->data = substr_replace($this->data, $ICCPChunk, $chunk['position'], 0);
                $this->updateRIFFHeader();
                break;
            }
        }
    }

    public function removeColorProfile(): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'ICCP') {
                $this->data = substr_replace($this->data, '', $chunk['offset'], $chunk['position'] - $chunk['offset']);
                $chunk['position'] = $chunk['offset'];
                $this->updateRIFFHeader();
            }

            if ($chunk['type'] === 'VP8X') {
                $VP8XFlags = ord($chunk['value'][0]) & ~self::ICC_FLAG;
                $this->data = substr_replace($this->data, chr($VP8XFlags), $chunk['offset'] + 8, 1);
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
            if ($chunk['type'] === 'EXIF') {
                return true;
            }
        }

        return false;
    }

    public function getExifData(): ?ExifData
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'EXIF') {
                return new ExifData($chunk['value']);
            }
        }

        return null;
    }

    public function setExifData(ExifData $exifData): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'VP8X') {
                $VP8XFlags = ord($chunk['value'][0]) | self::EXIF_FLAG;
                $this->data = substr_replace($this->data, chr($VP8XFlags), $chunk['offset'] + 8, 1);
            }

            if (in_array($chunk['type'], ['VP8 ', 'VP8L'], true)) {
                $ExifChunk = $this->encodeChunk('EXIF', $exifData->getData());
                $this->data = substr_replace($this->data, $ExifChunk, $chunk['position'], 0);
                $this->updateRIFFHeader();
            }
        }
    }

    public function removeExifData(): void
    {
        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'EXIF') {
                $this->data = substr_replace($this->data, '', $chunk['offset'], $chunk['position'] - $chunk['offset']);
                $chunk['position'] = $chunk['offset'];
                $this->updateRIFFHeader();
            }

            if ($chunk['type'] === 'VP8X') {
                $VP8XFlags = ord($chunk['value'][0]) & ~self::EXIF_FLAG;
                $this->data = substr_replace($this->data, chr($VP8XFlags), $chunk['offset'] + 8, 1);
            }
        }
    }

    protected function getDecoder(): WebpDecoder
    {
        return new WebpDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        imagesavealpha($gdImage, true);

        if (!imageistruecolor($gdImage)) {
            imagepalettetotruecolor($gdImage);
        }

        ob_start();

        if (imagewebp($gdImage, null, $this->options['webpQuality']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');

        $this->setVP8XChunk();
    }

    /**
     * Update RIFF header
     */
    private function updateRIFFHeader(): void
    {
        if (!str_starts_with($this->data, self::RIFF_HEADER)) {
            throw new InvalidArgumentException('Invalid WebP data');
        }
        $this->data = substr_replace($this->data, pack('V', strlen($this->data) - 8), 4, 4);
    }

    /**
     * Encode a WebP chunk
     */
    private function encodeChunk(string $type, string $data): string
    {
        $length = strlen($data);
        $data = $length % 2 !== 0 ? $data . "\x00" : $data;
        return $type . pack('V', $length) . $data;
    }

    /**
     * Set VP8X chunk
     */
    private function setVP8XChunk(): void
    {
        if (!str_contains(substr($this->data, 12), 'VP8X')) {
            $info = $this->getInfo();
            $VP8XFlags = $info->hasAlphaChannel() ? self::ALPHA_FLAG : self::NO_FLAG;
            $data = chr($VP8XFlags) . "\x0\x0\x0" . substr(pack('V', $info->width() - 1), 0, 3) . substr(pack('V', $info->height() - 1), 0, 3);
            $chunk = $this->encodeChunk('VP8X', $data);
            $this->data = substr_replace($this->data, $chunk, 12, 0);
            $this->updateRIFFHeader();
        }
    }

    /**
     * Unpack data from a binary string
     *
     * @return array<int|string, mixed>
     */
    private function unpack(string $format, string $string, int $offset = 0): array
    {
        return unpack($format, $string, $offset) ?: throw new UnexpectedValueException('Cannot unpack string');
    }
}
