<?php

namespace Formwork\Images\Exif;

use DateTimeImmutable;
use Stringable;

class ExifDateTime extends DateTimeImmutable implements Stringable
{
    /**
     * Date and time format used in EXIF data
     */
    public const string EXIF = 'Y:m:d H:i:s';

    /**
     * Extended date and time format used in EXIF data
     */
    public const string EXIF_EXTENDED = self::EXIF . '.uP';

    public function __toString(): string
    {
        return $this->format(self::EXIF_EXTENDED);
    }

    /**
     * Create an instance from EXIF data
     */
    public static function createFromExifData(string $datetime, ?string $subseconds = null, ?string $timeoffset = null): self|false
    {
        // EXIF strings can be padded with spaces or null bytes
        $datetime = trim($datetime, "\x00\x20");
        $subseconds = trim($subseconds ?? '', "\x00\x20");
        $timeoffset = trim($timeoffset ?? '', "\x00\x20");

        // Null bytes cannot be parsed as dates
        if (str_contains($datetime, "\x00")) {
            return false;
        }

        // Subseconds are the digits of a decimal fraction, of which only microseconds can be parsed
        $subseconds = ctype_digit($subseconds) ? substr($subseconds, 0, 6) : '0';

        if (!preg_match('/^[+-]\d{2}:\d{2}$/', $timeoffset)) {
            $timeoffset = '+00:00';
        }

        return parent::createFromFormat(self::EXIF_EXTENDED, "{$datetime}.{$subseconds}{$timeoffset}");
    }
}
