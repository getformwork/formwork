<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\PngDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

final class PngHandler extends AbstractHandler
{
    /**
     * Minimum size of the IHDR chunk
     */
    private const int IHDR_SIZE = 13;

    /**
     * Maximum length of the name of an embedded color profile
     */
    private const int MAX_PROFILE_NAME_LENGTH = 79;

    /**
     * Name used for embedded color profiles without a name
     */
    private const string DEFAULT_PROFILE_NAME = 'ICC profile';

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
                if ($chunk['size'] < self::IHDR_SIZE) {
                    throw new UnexpectedValueException('Invalid IHDR chunk');
                }
                $info['width'] = Bytes::unpack($chunk['value'], 0, 4);
                $info['height'] = Bytes::unpack($chunk['value'], 4, 4);
                $info['colorDepth'] = Bytes::unpack($chunk['value'], 8, 1);
                [$info['colorSpace'], $info['hasAlphaChannel']] = $this->getColorSpaceAndAlpha(Bytes::unpack($chunk['value'], 9, 1));
            }

            if ($chunk['type'] === 'PLTE') {
                if ($chunk['size'] % 3 > 0) {
                    throw new UnexpectedValueException('Invalid palette size');
                }
                $info['colorNumber'] = $chunk['size'] / 3;
            }

            if ($chunk['type'] === 'acTL') {
                $info['isAnimation'] = true;
                $info['animationFrames'] = Bytes::unpack($chunk['value'], 0, 4);
                $info['animationRepeatCount'] = Bytes::unpack($chunk['value'], 4, 4);
            }
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

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $chunk = $this->encodeChunk('iCCP', $this->encodeProfile($colorProfile->name(), $colorProfile->getData()));
        $this->replaceChunks('iCCP', $chunk);
    }

    public function removeColorProfile(): void
    {
        $this->replaceChunks('iCCP');
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

    public function setExifData(ExifData $exifData): void
    {
        $this->replaceChunks('eXIf', $this->encodeChunk('eXIf', $exifData->getData()));
    }

    public function removeExifData(): void
    {
        $this->replaceChunks('eXIf');
    }

    protected function getDecoder(): PngDecoder
    {
        return new PngDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        imagesavealpha($gdImage, true);

        ob_start();

        if (imagepng($gdImage, null, $this->options['pngCompression']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }

        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');
    }

    /**
     * Get color space and alpha channel from color type
     *
     * @return array{ColorSpace, bool}
     */
    private function getColorSpaceAndAlpha(int $colorType): array
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

    /**
     * Encode a PNG chunk
     */
    private function encodeChunk(string $name, string $data): string
    {
        return pack('N', strlen($data)) . $name . $data . pack('N', crc32($name . $data));
    }

    /**
     * Replace all the chunks of a given type with a new one placed right after the header chunk
     */
    private function replaceChunks(string $type, ?string $replacement = null): void
    {
        $replacements = [];
        $headerEnd = null;

        foreach ($this->decoder->decode($this->data) as $chunk) {
            if ($chunk['type'] === 'IHDR') {
                $headerEnd = $chunk['position'];
            }

            if ($chunk['type'] === $type) {
                $replacements[] = [$chunk['offset'], $chunk['position'] - $chunk['offset'], ''];
            }
        }

        if ($replacement !== null) {
            $replacements[] = [$headerEnd ?? throw new UnexpectedValueException('Missing IHDR chunk'), 0, $replacement];
        }

        $this->data = Bytes::replace($this->data, $replacements);
    }

    /**
     * Decode a PNG color profile chunk
     *
     * @return array{name: string, value: string}
     */
    private function decodeProfile(string $data): array
    {
        if (!extension_loaded('zlib')) {
            throw new RuntimeException(sprintf('%s() requires the extension "zlib" to be enabled', __METHOD__));
        }

        $reader = new ByteReader($data);
        $name = $reader->cString();
        $reader->skip(1);

        $value = @gzuncompress($reader->rest());

        if ($value === false || $value === '') {
            throw new UnexpectedValueException('Invalid color profile string');
        }

        return ['name' => $name, 'value' => $value];
    }

    /**
     * Encode a PNG color profile chunk
     */
    private function encodeProfile(string $name, string $value): string
    {
        if (!extension_loaded('zlib')) {
            throw new RuntimeException(sprintf('%s() requires the extension "zlib" to be enabled', __METHOD__));
        }

        $name = substr(trim($name), 0, self::MAX_PROFILE_NAME_LENGTH);

        return ($name === '' ? self::DEFAULT_PROFILE_NAME : $name) . "\x00\x00" . gzcompress($value);
    }
}
