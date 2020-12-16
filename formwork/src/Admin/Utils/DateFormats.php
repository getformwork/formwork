<?php

namespace Formwork\Admin\Utils;

use Formwork\Admin\Admin;
use DateTime;

class DateFormats
{
    /**
     * Characters used in formats accepted by date()
     *
     * @var string
     */
    protected const DATE_FORMAT_CHARACTERS = 'AaBcDdeFgGHhIijlLMmnNoOpPrsSTtUuvWwyYzZ';

    /**
     * Regex used to parse formats accepted by date()
     *
     * @var string
     */
    protected const DATE_FORMAT_REGEX = '/((?:\\\\[A-Za-z])+)|[' . self::DATE_FORMAT_CHARACTERS . ']/';

    /**
     * Regex used to parse date patterns like 'DD/MM/YYYY hh:mm:ss'
     *
     * @var string
     */
    protected const PATTERN_REGEX = '/(?:\[([^\]]+)\])|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]/';

    /**
     * Array used to translate pattern tokens to their date() format counterparts
     *
     * @var array
     */
    protected const PATTERN_TO_DATE_FORMAT = [
        'YY' => 'y', 'YYYY' => 'Y', 'M' => 'n', 'MM' => 'm', 'MMM' => 'M', 'MMMM' => 'F',
        'D'  => 'j', 'DD' => 'd', 'DDD' => 'D', 'DDDD' => 'l', 'W' => 'W', 'WW' => 'W',
        'RR' => 'o', 'RRRR' => 'o', 'H' => 'g', 'HH' => 'h', 'h' => 'G', 'hh' => 'H',
        'm'  => 'i', 'mm' => 'i', 's' => 's', 'ss' => 's', 'uuu' => 'v', 'A' => 'A',
        'a'  => 'a', 'Z' => 'P', 'z' => 'O'
    ];

    /**
     * Return common date formats
     */
    public static function date(): array
    {
        $formats = [];
        foreach (['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            $formats[$format] = date($format) . ' (' . static::formatToPattern($format) . ')';
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
            $formats[$format] = date($format) . ' (' . static::formatToPattern($format) . ')';
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

    /**
     * Convert a format accepted by date() to its corresponding pattern, e.g. the format 'd/m/Y \a\t h:i:s'
     * is converted to 'DD/MM/YYYY [at] hh:mm:ss'
     */
    public static function formatToPattern(string $format): string
    {
        $map = array_flip(self::PATTERN_TO_DATE_FORMAT);
        return preg_replace_callback(
            self::DATE_FORMAT_REGEX,
            static function (array $matches) use ($map): string {
                return isset($matches[1])
                    ? '[' . str_replace('\\', '', $matches[1]) . ']'
                    : ($map[$matches[0]] ?? $matches[0]);
            },
            $format
        );
    }

    /**
     * Convert a pattern to its corresponding format accepted by date(), e.g. the format
     * 'DDDD DD MMMM YYYY [at] HH:mm:ss A [o\' clock]' is converted to 'l d F Y \a\t h:i:s A \o\' \c\l\o\c\k',
     * where brackets are used to escape literal string portions
     */
    public static function patternToFormat(string $pattern): string
    {
        return preg_replace_callback(
            self::PATTERN_REGEX,
            static function (array $matches): string {
                return isset($matches[1])
                    ? addcslashes($matches[1], 'A..Za..z')
                    : (self::PATTERN_TO_DATE_FORMAT[$matches[0]] ?? $matches[0]);
            },
            $pattern
        );
    }

    /**
     * Formats a DateTime object using the current translation for weekdays and months
     */
    public static function formatDateTime(DateTime $dateTime, string $format): string
    {
        return preg_replace_callback(
            self::DATE_FORMAT_REGEX,
            static function (array $matches) use ($dateTime): string {
                switch ($matches[0]) {
                    case 'M':
                        return Admin::instance()->label('date.months.short')[$dateTime->format('n') - 1];
                    case 'F':
                        return Admin::instance()->label('date.months.long')[$dateTime->format('n') - 1];
                    case 'D':
                        return Admin::instance()->label('date.weekdays.short')[$dateTime->format('w')];
                    case 'l':
                        return Admin::instance()->label('date.weekdays.long')[$dateTime->format('w')];
                    case 'r':
                        return self::formatDateTime($dateTime, DateTime::RFC2822);
                    default:
                        return $dateTime->format($matches[1] ?? $matches[0]);
                }
            },
            $format
        );
    }

    /**
     * The same as self::formatDateTime() but takes a timestamp instead of a DateTime object
     */
    public static function formatTimestamp(int $timestamp, string $format): string
    {
        return static::formatDateTime(new DateTime('@' . $timestamp), $format);
    }
}
