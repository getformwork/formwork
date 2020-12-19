<?php

namespace Formwork\Utils;

use Formwork\Core\Formwork;
use DateTime;
use Exception;
use InvalidArgumentException;

class Date
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
     * Time intervals in seconds
     *
     * @var array
     */
    protected const TIME_INTERVALS = [
        'years'   => 60 * 60 * 24 * 365,
        'months'  => 60 * 60 * 24 * 30,
        'weeks'   => 60 * 60 * 24 * 7,
        'days'    => 60 * 60 * 24,
        'hours'   => 60 * 60,
        'minutes' => 60,
        'seconds' => 1
    ];

    /**
     * Parse a date according to a given format (or the default format if not given) and return the timestamp
     */
    public static function toTimestamp(string $date, string $format = null): int
    {
        $isFormatGiven = $format !== null;

        if (!$isFormatGiven) {
            $format = Formwork::instance()->config()->get('date.format');
        }

        $dateTime = DateTime::createFromFormat($format, $date);

        if ($dateTime === false) {
            if ($isFormatGiven) {
                throw new InvalidArgumentException('Date "' . $date . '" is not formatted according to the format "' . $format . '": ' . static::getLastDateTimeError());
            }
            try {
                $dateTime = new DateTime($date);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid date "' . $date . '": ' . static::getLastDateTimeError(), $e->getCode(), $e->getPrevious());
            }
        }

        return $dateTime->getTimestamp();
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
    public static function formatDateTime(DateTime $dateTime, string $format = null, string $language = null): string
    {
        if ($format === null) {
            $format = Formwork::instance()->config()->get('date.format');
        }

        if ($language === null) {
            $language = Formwork::instance()->translations()->getCurrent()->code();
        }

        $translation = Formwork::instance()->translations()->get($language);

        return preg_replace_callback(
            self::DATE_FORMAT_REGEX,
            static function (array $matches) use ($dateTime, $translation): string {
                switch ($matches[0]) {
                    case 'M':
                        return $translation->translate('date.months.short')[$dateTime->format('n') - 1];
                    case 'F':
                        return $translation->translate('date.months.long')[$dateTime->format('n') - 1];
                    case 'D':
                        return $translation->translate('date.weekdays.short')[$dateTime->format('w')];
                    case 'l':
                        return $translation->translate('date.weekdays.long')[$dateTime->format('w')];
                    case 'r':
                        return static::formatDateTime($dateTime, DateTime::RFC2822);
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
    public static function formatTimestamp(int $timestamp, string $format = null, string $language = null): string
    {
        return static::formatDateTime(new DateTime('@' . $timestamp), $format, $language);
    }

    /**
     * Formats a DateTime object as a time distance from now
     */
    public static function formatDateTimeAsDistance(DateTime $dateTime, string $language = null): string
    {
        if ($language === null) {
            $language = Formwork::instance()->translations()->getCurrent()->code();
        }

        $translation = Formwork::instance()->translations()->get($language);

        $time = $dateTime->getTimestamp();
        $now = time();

        if ($time < $now) {
            $difference = $now - $time;
            $format = 'date.distance.ago';
        } elseif ($time === $now) {
            $difference = 0;
            $format = 'date.now';
        } else {
            $difference = $time - $now;
            $format = 'date.distance.in';
        }

        foreach (self::TIME_INTERVALS as $intervalName => $seconds) {
            if (($interval = (int) round($difference / $seconds)) > 0) {
                $number = $interval !== 1 ? 1 : 0;
                $distance = sprintf(
                    '%d %s',
                    $interval,
                    $translation->translate('date.duration.' . $intervalName)[$number]
                );
                break;
            }
        }

        return $translation->translate($format, $distance ?? '');
    }

    /**
     * The same as self::formatDateTimeAsDistance() but takes a timestamp instead of a DateTime object
     */
    public static function formatTimestampAsDistance(int $timestamp, string $language = null): string
    {
        return static::formatDateTimeAsDistance(new DateTime('@' . $timestamp), $language);
    }

    /**
     * Return a human-readable string containing details about last DateTime error
     */
    protected static function getLastDateTimeError(): string
    {
        $result = [];
        $lastError = null;
        if (($errors = DateTime::getLastErrors()) !== false) {
            foreach ($errors['errors'] as $position => $error) {
                $currentError = lcfirst(rtrim($error, '.'));
                $result[] = ($currentError !== $lastError ? $currentError . ' at position ' : '') . $position;
                $lastError = $currentError;
            }
        }
        return implode(', ', $result);
    }
}
