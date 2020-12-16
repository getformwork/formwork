<?php

namespace Formwork\Utils;

use Formwork\Core\Formwork;
use DateTime;
use Exception;
use InvalidArgumentException;

class Date
{
    /**
     * Parse a date according to a given format (or the default format if not given) and return the timestamp
     *
     * @param $throwOnError Whether to throw an Exception when the method fails or trigger a deprecation error (@internal)
     */
    public static function toTimestamp(string $date, string $format = null, bool $throwOnError = false): int
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
                $message = 'Invalid date "' . $date . '": ' . static::getLastDateTimeError();
                // TODO: this will always be the case in 2.0
                if ($throwOnError) {
                    throw new InvalidArgumentException($message, $e->getCode(), $e->getPrevious());
                }
                trigger_error('Using invalid dates is deprecated since Formwork 1.11.0. ' . $message, E_USER_DEPRECATED);
            }
        }

        return $dateTime instanceof DateTime ? $dateTime->getTimestamp() : strtotime($date);
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
