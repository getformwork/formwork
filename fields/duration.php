<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Return the unit of the duration field
             *
             * This can be `seconds`, `minutes`, `hours`, `days`, `weeks`, `months`, or `years`.
             *
             * The default is `seconds`.
             */
            'unit' => function (Field $field): string {
                return $field->get('unit', 'seconds');
            },

            /**
             * Return the intervals of the duration field
             *
             * This is an array of intervals that can be used to display the duration in a more human-readable format.
             *
             * The default is `['days', 'hours', 'minutes', 'seconds']`.
             */
            'intervals' => function (Field $field): array {
                return $field->get('intervals', ['days', 'hours', 'minutes', 'seconds']);
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): int|float|null {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                if (!is_numeric($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                // This reliably casts numeric values to int or float
                $value += 0;

                if ($field->has('min') && $value < $field->get('min')) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be greater than or equal to %d', $field->name(), $field->type(), $field->get('min')), 'valueTooSmall', ['min' => $field->get('min')]);
                }

                if ($field->has('max') && $value > $field->get('max')) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be less than or equal to %d', $field->name(), $field->type(), $field->get('max')), 'valueTooLarge', ['max' => $field->get('max')]);
                }

                if ($field->has('step') && ($value - $field->get('min', 0)) % $field->get('step') !== 0) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not conform to the step value %d', $field->name(), $field->type(), $field->get('step')), 'stepMismatch', ['step' => $field->get('step')]);
                }

                if (!in_array($field->unit(), ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'])) {
                    throw new ValidationException(sprintf('Invalid unit for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                return $value;
            },
        ],
    ];
};
