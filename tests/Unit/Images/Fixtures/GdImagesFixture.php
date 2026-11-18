<?php

namespace Formwork\Tests\Unit\Images\Fixtures;

use GdImage;

/**
 * Provides small images encoded with GD, so that the handlers are tested against real data
 *
 * The encoded images are stored as files, since the output of the encoders changes with the versions of their libraries
 */
final class GdImagesFixture
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

    public static function png(): string
    {
        return self::read('image.png');
    }

    public static function jpeg(): string
    {
        return self::read('image.jpg');
    }

    public static function gif(): string
    {
        return self::read('image.gif');
    }

    public static function webp(): string
    {
        return self::read('image.webp');
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

    private static function read(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/' . $name);
    }
}
