<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Binary\Endianness;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\WebpDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

final class WebpHandler extends AbstractHandler
{
    /**
     * RIFF header
     */
    private const string RIFF_HEADER = 'RIFF';

    /**
     * WebP file header
     */
    private const string WEBP_HEADER = 'WEBP';

    /**
     * Size of the VP8X chunk
     */
    private const int VP8X_SIZE = 10;

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
                if ($chunk['size'] < self::VP8X_SIZE) {
                    throw new UnexpectedValueException('Invalid VP8X chunk');
                }
                $info['hasAlphaChannel'] = (Bytes::unpack($chunk['value'], 0, 1) & self::ALPHA_FLAG) !== 0;
                $info['width'] = Bytes::unpack($chunk['value'], 4, 3, Endianness::Little) + 1;
                $info['height'] = Bytes::unpack($chunk['value'], 7, 3, Endianness::Little) + 1;
                $isVP8ChunkParsed = true;
            }

            if (!$isVP8ChunkParsed && $chunk['type'] === 'VP8 ') {
                $info['width'] = Bytes::unpack($chunk['value'], 6, 2, Endianness::Little) & 0x3fff;
                $info['height'] = Bytes::unpack($chunk['value'], 8, 2, Endianness::Little) & 0x3fff;
                $isVP8ChunkParsed = true;
            }

            if (!$isVP8ChunkParsed && $chunk['type'] === 'VP8L') {
                $bits = Bytes::unpack($chunk['value'], 1, 4, Endianness::Little);
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
                $info['animationRepeatCount'] = Bytes::unpack($chunk['value'], 4, 2, Endianness::Little);
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
        $chunks = $this->withoutChunks($this->extendedChunks(), 'ICCP');

        // The color profile must immediately follow the VP8X chunk
        array_splice($chunks, 1, 0, [['ICCP', $colorProfile->getData()]]);

        $this->setChunks($chunks);
    }

    public function removeColorProfile(): void
    {
        $this->setChunks($this->withoutChunks($this->chunks(), 'ICCP'));
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
        $chunks = $this->withoutChunks($this->extendedChunks(), 'EXIF');

        // EXIF metadata follows the image data and precedes XMP metadata
        $position = count($chunks);

        foreach ($chunks as $index => [$type]) {
            if ($type === 'XMP ') {
                $position = $index;
                break;
            }
        }

        array_splice($chunks, $position, 0, [['EXIF', $exifData->getData()]]);

        $this->setChunks($chunks);
    }

    public function removeExifData(): void
    {
        $this->setChunks($this->withoutChunks($this->chunks(), 'EXIF'));
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

        $this->setChunks($this->extendedChunks());
    }

    /**
     * Get the chunks of the image
     *
     * @return list<array{string, string}>
     */
    private function chunks(): array
    {
        $chunks = [];

        foreach ($this->decoder->decode($this->data) as $chunk) {
            $chunks[] = [$chunk['type'], $chunk['value']];
        }

        return $chunks;
    }

    /**
     * Get the chunks of the image, ensuring that the first one is a VP8X chunk
     *
     * @return list<array{string, string}>
     */
    private function extendedChunks(): array
    {
        $chunks = $this->chunks();

        if (($chunks[0][0] ?? null) === 'VP8X') {
            return $chunks;
        }

        $info = $this->getInfo();

        $flags = $info->hasAlphaChannel() ? self::ALPHA_FLAG : self::NO_FLAG;

        $canvas = Bytes::pack($info->width() - 1, 3, Endianness::Little) . Bytes::pack($info->height() - 1, 3, Endianness::Little);

        return [['VP8X', Bytes::pack($flags, 1) . "\x00\x00\x00" . $canvas], ...$chunks];
    }

    /**
     * Remove all the chunks of a given type
     *
     * @param list<array{string, string}> $chunks
     *
     * @return list<array{string, string}>
     */
    private function withoutChunks(array $chunks, string $type): array
    {
        return array_values(array_filter($chunks, static fn(array $chunk): bool => $chunk[0] !== $type));
    }

    /**
     * Set the chunks of the image, updating the flags of the VP8X chunk accordingly
     *
     * @param list<array{string, string}> $chunks
     */
    private function setChunks(array $chunks): void
    {
        $types = array_column($chunks, 0);

        if (($chunks[0][0] ?? null) === 'VP8X') {
            $flags = Bytes::unpack($chunks[0][1], 0, 1) & ~(self::ICC_FLAG | self::EXIF_FLAG);
            $flags |= in_array('ICCP', $types, true) ? self::ICC_FLAG : self::NO_FLAG;
            $flags |= in_array('EXIF', $types, true) ? self::EXIF_FLAG : self::NO_FLAG;
            $chunks[0][1] = Bytes::pack($flags, 1) . substr($chunks[0][1], 1);
        }

        $data = self::WEBP_HEADER;

        foreach ($chunks as [$type, $value]) {
            $data .= $this->encodeChunk($type, $value);
        }

        $this->data = self::RIFF_HEADER . Bytes::pack(strlen($data), 4, Endianness::Little) . $data;
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
}
