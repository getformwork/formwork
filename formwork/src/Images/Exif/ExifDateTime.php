<?php

namespace Formwork\Images\Exif;

use DateTimeImmutable;
use Stringable;

class ExifDateTime extends DateTimeImmutable implements Stringable
{
    public const EXIF = 'Y:m:d H:i:s';

    public const EXIF_EXTENDED = self::EXIF . '.uP';

    public function __toString(): string
    {
        return $this->format(self::EXIF_EXTENDED);
    }

    /**
     * Create a new ExifDateTime object from EXIF tags, e.g. DateTime, SubSecTime, TimeOffset
     *
     * @return bool|ExifDateTime
     */
    public static function createFromExifData(string $datetime, ?string $subseconds = null, ?string $timeoffset = null)
    {
        return parent::createFromFormat(self::EXIF_EXTENDED, sprintf('%s.%s%s', $datetime, rtrim($subseconds ?? '0', "\x00\x20"), $timeoffset ?? '+00:00'));
    }
}
