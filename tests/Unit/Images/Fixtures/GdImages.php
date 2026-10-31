<?php

namespace Formwork\Tests\Unit\Images\Fixtures;

use GdImage;
use RuntimeException;

/**
 * Encodes small images with GD, so that the handlers are tested against real data
 */
final class GdImages
{
    /**
     * Create a truecolor image with a transparent corner
     */
    public static function image(int $width = 40, int $height = 30): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 200, 40, 40, 0));
        imagefilledrectangle($image, 0, 0, 9, 9, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));

        return $image;
    }

    public static function png(int $width = 40, int $height = 30): string
    {
        return self::encode(static fn (GdImage $image): bool => imagepng($image), $width, $height);
    }

    public static function jpeg(int $width = 40, int $height = 30): string
    {
        return self::encode(static fn (GdImage $image): bool => imagejpeg($image), $width, $height);
    }

    public static function gif(int $width = 40, int $height = 30): string
    {
        return self::encode(static fn (GdImage $image): bool => imagegif($image), $width, $height);
    }

    public static function webp(int $width = 40, int $height = 30): string
    {
        return self::encode(static fn (GdImage $image): bool => imagewebp($image), $width, $height);
    }

    /**
     * Encode a PNG chunk
     */
    public static function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }

    /**
     * Encode a WebP chunk
     */
    public static function webpChunk(string $type, string $data): string
    {
        return $type . pack('V', strlen($data)) . $data . (strlen($data) % 2 !== 0 ? "\x00" : '');
    }

    /**
     * Encode a WebP file
     */
    public static function webpFile(string ...$chunks): string
    {
        $body = 'WEBP' . implode('', $chunks);

        return 'RIFF' . pack('V', strlen($body)) . $body;
    }

    /**
     * Encode a JPEG segment
     */
    public static function jpegSegment(int $type, string $data): string
    {
        return "\xff" . chr($type) . pack('n', strlen($data) + 2) . $data;
    }

    /**
     * @param callable(GdImage): bool $encoder
     */
    private static function encode(callable $encoder, int $width, int $height): string
    {
        ob_start();

        if (!$encoder(self::image($width, $height))) {
            ob_end_clean();
            throw new RuntimeException('Cannot encode image');
        }

        return (string) ob_get_clean();
    }
}
