<?php

namespace Formwork\Panel\Utils;

use Formwork\Traits\StaticClass;
use Formwork\Utils\Date;

final class DateFormats
{
    use StaticClass;

    /**
     * Return common date formats
     *
     * @return array<string, string>
     */
    public static function date(): array
    {
        $formats = [];
        foreach (['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            $formats[$format] = date($format) . ' (' . Date::formatToPattern($format) . ')';
        }
        return $formats;
    }

    /**
     * Return common hour formats
     *
     * @return array<string, string>
     */
    public static function hour(): array
    {
        $formats = [];
        foreach (['H:i', 'h:i A'] as $format) {
            $formats[$format] = date($format) . ' (' . Date::formatToPattern($format) . ')';
        }
        return $formats;
    }

    /**
     * Return timezone identifiers
     *
     * @return array<string, string>
     */
    public static function timezones(): array
    {
        $timezones = [];
        foreach (timezone_identifiers_list() as $tz) {
            $timezones[$tz] = str_replace('_', ' ', $tz);
        }
        return $timezones;
    }
}
