<?php

namespace Formwork\Admin\Utils;

class DateFormats
{
    /**
     * Return common date formats
     *
     * @return array
     */
    public static function date()
    {
        $formats = array();
        foreach (array('d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y') as $format) {
            $formats[$format] = date($format) . ' (' . $format . ')';
        }
        return $formats;
    }

    /**
     * Return common hour formats
     *
     * @return array
     */
    public static function hour()
    {
        $formats = array();
        foreach (array('H:i', 'h:i A') as $format) {
            $formats[$format] = date($format) . ' (' . $format . ')';
        }
        return $formats;
    }

    /**
     * Return timezone identifiers
     *
     * @return array
     */
    public static function timezones()
    {
        $timezones = array();
        foreach (timezone_identifiers_list() as $tz) {
            $timezones[$tz] = str_replace('_', ' ', $tz);
        }
        return $timezones;
    }
}
