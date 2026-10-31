<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\JpegDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

final class JpegHandler extends AbstractHandler
{
    /**
     * Maximum number of bytes in a segment
     */
    private const int MAX_BYTES_IN_SEGMENT = 65533;

    /**
     * Minimum size of a start of frame segment
     */
    private const int SOF_SIZE = 6;

    /**
     * Maximum number of chunks of an ICC profile
     */
    private const int MAX_PROFILE_CHUNKS = 255;

    /**
     * JFIF header
     */
    private const string JFIF_HEADER = "JFIF\x00";

    /**
     * Image EXIF header
     */
    private const string EXIF_HEADER = "Exif\x00\x00";

    /**
     * Image ICC profile header
     */
    private const string ICC_PROFILE_HEADER = "ICC_PROFILE\x00";

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
            if ($this->isStartOfFrame($segment['type'])) {
                if ($segment['size'] - 2 < self::SOF_SIZE) {
                    throw new UnexpectedValueException('Invalid start of frame segment');
                }
                $info['colorDepth'] = Bytes::unpack($segment['value'], 0, 1);
                $info['height'] = Bytes::unpack($segment['value'], 1, 2);
                $info['width'] = Bytes::unpack($segment['value'], 3, 2);
                $info['colorSpace'] = $this->getColorSpace(Bytes::unpack($segment['value'], 5, 1));
                break;
            }
        }

        return new ImageInfo($info);
    }

    public function supportsTransforms(): bool
    {
        return true;
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
        $profileChunks = [];
        $chunkCount = 0;

        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xe2 && str_starts_with($segment['value'], self::ICC_PROFILE_HEADER)) {
                $reader = new ByteReader($segment['value'], strlen(self::ICC_PROFILE_HEADER));
                $chunkNum = $reader->uint(1);
                $chunkCount = $reader->uint(1);
                $profileChunks[$chunkNum] = $reader->rest();
            }
        }

        if ($profileChunks === []) {
            return null;
        }

        ksort($profileChunks);

        if (array_keys($profileChunks) !== range(1, $chunkCount)) {
            throw new UnexpectedValueException('Unexpected profile chunk count');
        }

        return new ColorProfile(implode('', $profileChunks));
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $this->replaceSegments(0xe2, self::ICC_PROFILE_HEADER, $this->encodeColorProfile($colorProfile->getData()));
    }

    public function removeColorProfile(): void
    {
        $this->replaceSegments(0xe2, self::ICC_PROFILE_HEADER);
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
        $this->replaceSegments(0xe1, self::EXIF_HEADER, $this->encodeExifData($exifData->getData()));
    }

    public function removeExifData(): void
    {
        $this->replaceSegments(0xe1, self::EXIF_HEADER);
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

        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');
    }

    /**
     * Whether a marker is a start of frame (SOF) one
     */
    private function isStartOfFrame(int $type): bool
    {
        return $type >= 0xc0 && $type <= 0xcf && !in_array($type, [0xc4, 0xc8, 0xcc], true);
    }

    /**
     * Replace all the segments of a given type and header with new ones
     *
     * New segments are placed right after the start of image marker, or after the JFIF
     * segment if present, since it is required to immediately follow the former.
     */
    private function replaceSegments(int $type, string $header, ?string $replacement = null): void
    {
        $replacements = [];
        $insertPosition = null;

        foreach ($this->decoder->decode($this->data) as $segment) {
            if ($segment['type'] === 0xd8) {
                $insertPosition = $segment['position'];
                continue;
            }

            if ($insertPosition === null) {
                throw new UnexpectedValueException('Missing start of image marker');
            }

            if ($segment['type'] === 0xe0 && $segment['offset'] === $insertPosition && str_starts_with($segment['value'], self::JFIF_HEADER)) {
                $insertPosition = $segment['position'];
            }

            if ($segment['type'] === $type && str_starts_with($segment['value'], $header)) {
                $replacements[] = [$segment['offset'], $segment['position'] - $segment['offset'], ''];
            }
        }

        if ($replacement !== null) {
            $replacements[] = [$insertPosition ?? throw new UnexpectedValueException('Missing start of image marker'), 0, $replacement];
        }

        $this->data = Bytes::replace($this->data, $replacements);
    }

    /**
     * Get color space from number of components
     */
    private function getColorSpace(int $components): ColorSpace
    {
        return match ($components) {
            1       => ColorSpace::Grayscale,
            3       => ColorSpace::RGB,
            4       => ColorSpace::CMYK,
            default => throw new UnexpectedValueException('Invalid color space'),
        };
    }

    /**
     * Encode ICC profile data into JPEG segments
     */
    private function encodeColorProfile(string $data): string
    {
        $maxChunkSize = self::MAX_BYTES_IN_SEGMENT - strlen(self::ICC_PROFILE_HEADER) - 4;
        $chunks = str_split($data, $maxChunkSize);
        $count = count($chunks);

        if ($count > self::MAX_PROFILE_CHUNKS) {
            throw new UnexpectedValueException('Color profile is too large to be embedded in a JPEG image');
        }

        for ($i = 0; $i < $count; $i++) {
            $chunks[$i] = $this->encodeSegment(0xe2, self::ICC_PROFILE_HEADER . Bytes::pack($i + 1, 1) . Bytes::pack($count, 1) . $chunks[$i]);
        }

        return implode('', $chunks);
    }

    /**
     * Encode EXIF data into a JPEG segment
     */
    private function encodeExifData(string $data): string
    {
        return $this->encodeSegment(0xe1, self::EXIF_HEADER . $data);
    }

    /**
     * Encode a JPEG segment
     */
    private function encodeSegment(int $type, string $value): string
    {
        if (strlen($value) > self::MAX_BYTES_IN_SEGMENT) {
            throw new UnexpectedValueException('Data is too large to be embedded in a JPEG segment');
        }

        return "\xff" . chr($type) . Bytes::pack(strlen($value) + 2, 2) . $value;
    }
}
