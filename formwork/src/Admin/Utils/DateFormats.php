<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\Date;

class DateFormats
{
    /**
     * Return common date formats
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
