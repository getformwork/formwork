<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Return the minimum allowed value for the field
             *
             * If not set, no minimum value is enforced
             */
            'min' => function (Field $field): ?int {
                return $field->get('min');
            },

            /**
             * Return the maximum allowed value for the field
             *
             * If not set, no maximum value is enforced
             */
            'max' => function (Field $field): ?int {
                return $field->get('max');
            },

            /**
             * Return the step value for the field
             *
             * If not set, no step value is enforced
             */
            'step' => function (Field $field): ?int {
                return $field->get('step');
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): int|float {
                if (!is_numeric($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                // This reliably casts numeric values to int or float
                $value += 0;

                if ($field->has('min') && $value < $field->min()) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be greater than or equal to %d', $field->name(), $field->type(), $field->get('min')));
                }

                if ($field->has('max') && $value > $field->max()) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be less than or equal to %d', $field->name(), $field->type(), $field->get('max')));
                }

                if ($field->has('step') && ($value - $field->get('min', 0)) % $field->step() !== 0) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not conform to the step value %d', $field->name(), $field->value(), $field->get('step')));
                }

                return $value;
            },
        ],
    ];
};
