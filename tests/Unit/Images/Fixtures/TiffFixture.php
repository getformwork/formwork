<?php

namespace Formwork\Tests\Unit\Images\Fixtures;

/**
 * Encodes TIFF data with arbitrary EXIF tags, like the ones embedded in images
 *
 * Each tag is given as `id => [type, value]`, where the value is a string for the byte, ASCII and undefined types,
 * an integer or a list of integers for the short and long types, and a list of `[numerator, denominator]` pairs
 * for the rational types
 */
final class TiffFixture
{
    public const int BYTE = 1;

    public const int ASCII = 2;

    public const int SHORT = 3;

    public const int LONG = 4;

    public const int RATIONAL = 5;

    public const int UNDEFINED = 7;

    public const int SRATIONAL = 10;

    /**
     * Tag of the pointer to the EXIF IFD
     */
    private const int EXIF_POINTER = 0x8769;

    /**
     * Tag of the pointer to the GPS IFD
     */
    private const int GPS_POINTER = 0x8825;

    /**
     * Length of the TIFF header
     */
    private const int HEADER_LENGTH = 8;

    /**
     * @param array<int, array{int, mixed}> $ifd0
     * @param array<int, array{int, mixed}> $exif
     * @param array<int, array{int, mixed}> $gps
     */
    public static function tiff(array $ifd0, array $exif = [], array $gps = [], bool $bigEndian = false): string
    {
        $ifds = [];

        if ($exif !== []) {
            $ifd0[self::EXIF_POINTER] = [self::LONG, 0];
        }

        if ($gps !== []) {
            $ifd0[self::GPS_POINTER] = [self::LONG, 0];
        }

        $offset = self::HEADER_LENGTH + self::length($ifd0);

        if ($exif !== []) {
            $ifd0[self::EXIF_POINTER] = [self::LONG, $offset];
            $ifds[] = [$exif, $offset];
            $offset += self::length($exif);
        }

        if ($gps !== []) {
            $ifd0[self::GPS_POINTER] = [self::LONG, $offset];
            $ifds[] = [$gps, $offset];
        }

        $data = $bigEndian ? "MM\x00\x2a" . pack('N', self::HEADER_LENGTH) : "II\x2a\x00" . pack('V', self::HEADER_LENGTH);
        $data .= self::ifd($ifd0, self::HEADER_LENGTH, $bigEndian);

        foreach ($ifds as [$tags, $offset]) {
            $data .= self::ifd($tags, $offset, $bigEndian);
        }

        return $data;
    }

    /**
     * Encode an IFD, followed by the values that do not fit in its entries
     *
     * @param array<int, array{int, mixed}> $tags
     */
    private static function ifd(array $tags, int $offset, bool $bigEndian): string
    {
        ksort($tags);

        $entries = self::short(count($tags), $bigEndian);
        $values = '';
        $valuesOffset = $offset + 2 + 12 * count($tags) + 4;

        foreach ($tags as $tag => [$type, $value]) {
            [$count, $bytes] = self::encode($type, $value, $bigEndian);

            $entries .= self::short($tag, $bigEndian) . self::short($type, $bigEndian) . self::long($count, $bigEndian);

            if (strlen($bytes) <= 4) {
                $entries .= str_pad($bytes, 4, "\x00");
            } else {
                $entries .= self::long($valuesOffset + strlen($values), $bigEndian);
                $values .= self::pad($bytes);
            }
        }

        return $entries . self::long(0, $bigEndian) . $values;
    }

    /**
     * Get the length of an encoded IFD
     *
     * @param array<int, array{int, mixed}> $tags
     */
    private static function length(array $tags): int
    {
        return strlen(self::ifd($tags, 0, false));
    }

    /**
     * Encode a value, returning its count and its bytes
     *
     * @return array{int, string}
     */
    private static function encode(int $type, mixed $value, bool $bigEndian): array
    {
        return match ($type) {
            self::BYTE, self::UNDEFINED => [strlen($value), $value],
            self::ASCII                 => [strlen($value) + 1, "{$value}\x00"],
            self::SHORT                 => [count((array) $value), implode('', array_map(static fn (int $number): string => self::short($number, $bigEndian), (array) $value))],
            self::LONG                  => [count((array) $value), implode('', array_map(static fn (int $number): string => self::long($number, $bigEndian), (array) $value))],
            self::RATIONAL, self::SRATIONAL => [count($value), implode('', array_map(static fn (array $rational): string => self::long($rational[0], $bigEndian) . self::long($rational[1], $bigEndian), $value))],
        };
    }

    private static function short(int $number, bool $bigEndian): string
    {
        return pack($bigEndian ? 'n' : 'v', $number);
    }

    private static function long(int $number, bool $bigEndian): string
    {
        // Negative numbers are encoded in two's complement for the signed types
        return pack($bigEndian ? 'N' : 'V', $number & 0xFFFFFFFF);
    }

    /**
     * Pad values to an even length, since they must begin on a word boundary
     */
    private static function pad(string $bytes): string
    {
        return strlen($bytes) % 2 === 0 ? $bytes : "{$bytes}\x00";
    }
}
