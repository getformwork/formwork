<?php

namespace Formwork\Admin\Utils;

class DateFormats {

    public static function date() {
        foreach (array('d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y') as $format) {
            $formats[$format] = date($format) . ' (' . $format . ')';
        }
        return $formats;
    }

    public static function hour() {
        foreach (array('H:i', 'h:i A') as $format) {
            $formats[$format] = date($format) . ' (' . $format . ')';
        }
        return $formats;
    }

    public static function timezones() {
        foreach (timezone_identifiers_list() as $tz) {
            $timezones[$tz] = str_replace('_', ' ', $tz);
        }
        return $timezones;
    }

}
