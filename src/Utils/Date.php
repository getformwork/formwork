<?php

namespace Formwork\Utils;

use DateTime;
use Exception;
use Formwork\Traits\StaticClass;
use Formwork\Translations\Translation;
use InvalidArgumentException;
use RuntimeException;

final class Date
{
    use StaticClass;

    /**
     * Characters used in formats accepted by `date()`
     */
    private const string DATE_FORMAT_CHARACTERS = 'AaBcDdeFgGHhIijlLMmnNoOpPrsSTtUuvWwyYzZ';

    /**
     * Regex used to parse formats accepted by `date()`
     */
    private const string DATE_FORMAT_REGEX = '/(?P<escaped>(?:\\\[A-Za-z])+)|[' . self::DATE_FORMAT_CHARACTERS . ']|(?P<invalid>[A-Za-z])/';

    /**
     * Regex used to parse date patterns like `DD/MM/YYYY hh:mm:ss`
     */
    private const string PATTERN_REGEX = '/(?:\[(?P<escaped>[^\]]+)\])|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]|(?P<invalid>[A-Za-z]+)/';

    /**
     * Array used to translate pattern tokens to their `date()` format counterparts
     *
     * @var array<string, string>
     */
    private const array PATTERN_TO_DATE_FORMAT = [
        'YY'   => 'y',
        'YYYY' => 'Y',
        'M'    => 'n',
        'MM'   => 'm',
        'MMM'  => 'M',
        'MMMM' => 'F',
        'D'    => 'j',
        'DD'   => 'd',
        'DDD'  => 'D',
        'DDDD' => 'l',
        'W'    => 'W',
        'WW'   => 'W',
        'RR'   => 'o',
        'RRRR' => 'o',
        'H'    => 'g',
        'HH'   => 'h',
        'h'    => 'G',
        'hh'   => 'H',
        'm'    => 'i',
        'mm'   => 'i',
        's'    => 's',
        'ss'   => 's',
        'uuu'  => 'v',
        'A'    => 'A',
        'a'    => 'a',
        'Z'    => 'P',
        'z'    => 'O',
    ];

    /**
     * Time intervals in seconds
     *
     * @var array<string, int>
     */
    private const array TIME_INTERVALS = [
        'years'   => 60 * 60 * 24 * 365,
        'months'  => 60 * 60 * 24 * 30,
        'weeks'   => 60 * 60 * 24 * 7,
        'days'    => 60 * 60 * 24,
        'hours'   => 60 * 60,
        'minutes' => 60,
        'seconds' => 1,
    ];

    /**
     * Parse a date according to a given format (or the default format if not given) and return the timestamp
     *
     * @param string               $date   The date string to parse
     * @param array<string>|string $format Format(s) to try when parsing the date
     *
     * @throws InvalidArgumentException If the date cannot be parsed according to the given format
     */
    public static function toTimestamp(string $date, string|array $format): int
    {
        try {
            $dateTime = self::createDateTime($date, (array) $format);
        } catch (InvalidArgumentException $e) {
            // Try to parse the date anyway if the format is not given
            try {
                $dateTime = new DateTime($date);
            } catch (Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid date "%s": %s', $date, self::getLastDateTimeError()), $e->getCode(), $e->getPrevious());
            }
        }

        return $dateTime->getTimestamp();
    }

    /**
     * Convert a format accepted by `date()` to its corresponding pattern.
     *
     * For instance, the format `d/m/Y \a\t h:i:s` is converted to `DD/MM/YYYY [at] hh:mm:ss`
     *
     * @throws RuntimeException If the format conversion fails due to a regex error
     */
    public static function formatToPattern(string $format): string
    {
        $map = array_flip(self::PATTERN_TO_DATE_FORMAT);
        return preg_replace_callback(
            self::DATE_FORMAT_REGEX,
            fn(array $matches): string => match (true) {
                isset($matches['invalid']) => '',
                isset($matches['escaped']) => '[' . str_replace('\\', '', $matches['escaped']) . ']',
                default                    => $map[$matches[0]] ?? '',
            },
            $format
        ) ?? throw new RuntimeException(sprintf('Format conversion failed with error: %s', preg_last_error_msg()));
    }

    /**
     * Convert a pattern to its corresponding format accepted by `date()`
     *
     * For instance, the format `DDDD DD MMMM YYYY [at] HH:mm:ss A [o\' clock]`
     * is converted to `l d F Y \a\t h:i:s A \o\' \c\l\o\c\k`,
     * where brackets are used to escape literal string portions
     *
     * @throws RuntimeException If the pattern conversion fails due to a regex error
     */
    public static function patternToFormat(string $pattern): string
    {
        return preg_replace_callback(
            self::PATTERN_REGEX,
            fn(array $matches): string => match (true) {
                isset($matches['invalid']) => '',
                isset($matches['escaped']) => addcslashes($matches['escaped'], 'A..Za..z'),
                default                    => self::PATTERN_TO_DATE_FORMAT[$matches[0]] ?? '',
            },
            $pattern
        ) ?? throw new RuntimeException(sprintf('Format conversion failed with error: %s', preg_last_error_msg()));
    }

    /**
     * Formats a DateTime object using the current translation for weekdays and months
     *
     * @param DateTime    $dateTime    The `DateTime` object to format
     * @param string      $format      The format string accepted by `date()`
     * @param Translation $translation The translation object for localized strings
     *
     * @throws RuntimeException If the date formatting fails due to a regex error
     */
    public static function formatDateTime(DateTime $dateTime, string $format, Translation $translation): string
    {
        return preg_replace_callback(
            self::DATE_FORMAT_REGEX,
            fn(array $matches): string => match ($matches[0]) {
                'M'     => $translation->getStrings('date.months.short')[$dateTime->format('n') - 1],
                'F'     => $translation->getStrings('date.months.long')[$dateTime->format('n') - 1],
                'D'     => $translation->getStrings('date.weekdays.short')[(int) $dateTime->format('w')],
                'l'     => $translation->getStrings('date.weekdays.long')[(int) $dateTime->format('w')],
                'r'     => self::formatDateTime($dateTime, DateTime::RFC2822, $translation),
                default => $dateTime->format($matches[1] ?? $matches[0]),
            },
            $format
        ) ?? throw new RuntimeException(sprintf('Date formatting failed with error: %s', preg_last_error_msg()));
    }

    /**
     * The same as `formatDateTime()` but takes a timestamp instead of a `DateTime` object
     */
    public static function formatTimestamp(int $timestamp, string $format, Translation $translation): string
    {
        return self::formatDateTime((new DateTime())->setTimestamp($timestamp), $format, $translation);
    }

    /**
     * Formats a `DateTime` object as a time distance from now
     */
    public static function formatDateTimeAsDistance(DateTime $dateTime, Translation $translation): string
    {
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
            if (($interval = (int) floor($difference / $seconds)) > 0) {
                $number = $interval !== 1 ? 1 : 0;
                $distance = sprintf(
                    '%d %s',
                    $interval,
                    $translation->getStrings("date.duration.{$intervalName}")[$number]
                );
                break;
            }
        }

        /**
         * @var string
         */
        return $translation->translate($format, $distance ?? '');
    }

    /**
     * The same as `formatDateTimeAsDistance()` but takes a timestamp instead of a `DateTime` object
     */
    public static function formatTimestampAsDistance(int $timestamp, Translation $translation): string
    {
        return self::formatDateTimeAsDistance((new DateTime())->setTimestamp($timestamp), $translation);
    }

    /**
     * Create a `DateTime` object from a date string and a list of formats
     *
     * @param array<string> $formats
     *
     * @throws InvalidArgumentException If no formats are provided or if the date cannot be parsed
     */
    private static function createDateTime(string $date, array $formats): DateTime
    {
        if ($formats === []) {
            throw new InvalidArgumentException(sprintf('At least 1 format must be given to %s()', __METHOD__));
        }
        foreach ($formats as $format) {
            // Always prepend "!" to the format to avoid parsing the date as local time
            $dateTime = DateTime::createFromFormat(Str::prepend($format, '!'), $date);
            if ($dateTime !== false) {
                return $dateTime;
            }
        }
        throw new InvalidArgumentException(sprintf('Date "%s" is not formatted according to the format "%s": %s', $date, $format, self::getLastDateTimeError()));
    }

    /**
     * Return a human-readable string containing details about last `DateTime` error
     */
    private static function getLastDateTimeError(): string
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
