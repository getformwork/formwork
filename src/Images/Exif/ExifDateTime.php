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
        return parent::createFromFormat(self::EXIF_EXTENDED, sprintf('%s.%s%s', $datetime, rtrim($subseconds ?? '0', "\x00\x20"), $timeoffset ?? '+00:00'));
    }
}
