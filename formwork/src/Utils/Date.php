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
