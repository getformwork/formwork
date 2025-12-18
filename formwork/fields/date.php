<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;
use Formwork\Utils\Date;
use Formwork\Utils\Str;

return function (App $app): array {
    return [
        'methods' => [
            /**
             * Return the field value formatted as a date string
             *
             * By default the date is formatted using the `YYYY-MM-DD` format.
             * This is at the same time human-readable and comparable when sorting.
             *
             * @param string $format the format to use for the date string
             * @param string $type   the type of format to use, either `pattern` or `date`
             */
            'format' => function (Field $field, string $format = 'YYYY-MM-DD', string $type = 'pattern') use ($app): string {
                $translation = $app->translations()->getCurrent();

                $format = match (strtolower($type)) {
                    'pattern' => Date::patternToFormat($format),
                    'date'    => $format,
                    default   => throw new InvalidArgumentException('Invalid date format type'),
                };

                return $field->isEmpty() ? '' : Date::formatTimestamp($field->toTimestamp(), $format, $translation);
            },

            /**
             * Return the field value as a timestamp
             */
            'toTimestamp' => function (Field $field) use ($app): ?int {
                $formats = [
                    $app->config()->get('system.date.dateFormat'),
                    $app->config()->get('system.date.datetimeFormat'),
                ];
                return $field->isEmpty() ? null : Date::toTimestamp($field->value(), $formats);
            },

            /**
             * Return the field value as a duration string.
             *
             * The duration is formatted as a human-readable translated string representing
             * the time difference between the field value and the current time.
             */
            'toDuration' => function (Field $field) use ($app): string {
                return $field->isEmpty() ? '' : Date::formatTimestampAsDistance($field->toTimestamp(), $app->translations()->getCurrent());
            },

            /**
             * Return the field value as a date string in [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) format
             *
             * The time part is removed if the field does not have a time component.
             */
            'toDateTimeString' => function (Field $field): string {
                return $field->isEmpty() ? '' : Str::removeEnd($field->format('YYYY-MM-DD[T]hh:mm:ss'), ':00');
            },

            /**
             * Return the field value as a formatted string
             */
            'toString' => function (Field $field): string {
                return $field->isEmpty() ? '' : $field->format();
            },

            'return' => function (Field $field): Field {
                return $field;
            },

            /**
             * Return whether the field has a time component
             */
            'hasTime' => function (Field $field): bool {
                return $field->is('time', true);
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value) use ($app): ?string {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                $inputFormats = [
                    $app->config()->get('system.date.dateFormat'),
                    $app->config()->get('system.date.datetimeFormat'),
                ];

                $format = $field->hasTime()
                    ? 'Y-m-d H:i:s'
                    : 'Y-m-d';

                try {
                    return date($format, Date::toTimestamp($value, $inputFormats));
                } catch (InvalidArgumentException $e) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s":%s', $field->name(), $field->type(), Str::after($e->getMessage(), ':')));
                }
            },
        ],
    ];
};
