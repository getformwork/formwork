<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'translate' => ['label', 'description', 'suggestion', 'placeholder'],

        'methods' => [
            /**
             * Return the minimum allowed value for the field
             *
             * If not set, no minimum value is enforced
             */
            'min' => function (Field $field): int|float|null {
                return $field->get('min');
            },

            /**
             * Return the maximum allowed value for the field
             *
             * If not set, no maximum value is enforced
             */
            'max' => function (Field $field): int|float|null {
                return $field->get('max');
            },

            /**
             * Return the step value for the field
             *
             * If not set, no step value is enforced. Like in browsers, steps that are not positive numbers fall back to 1
             */
            'step' => function (Field $field): int|float|null {
                $step = $field->get('step');

                if ($step === null) {
                    return null;
                }

                return is_numeric($step) && $step > 0 ? $step + 0 : 1;
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

                if ($field->has('min') && $value < $field->min()) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be greater than or equal to %s', $field->name(), $field->type(), $field->get('min')), 'valueTooSmall', ['min' => $field->get('min')]);
                }

                if ($field->has('max') && $value > $field->max()) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be less than or equal to %s', $field->name(), $field->type(), $field->get('max')), 'valueTooLarge', ['max' => $field->get('max')]);
                }

                if ($field->has('step')) {
                    // The value must be an integral number of steps from the minimum, tolerating the errors of floating point arithmetic
                    $steps = ($value - $field->get('min', 0)) / $field->step();

                    if (abs($steps - round($steps)) > 2 ** -24) {
                        throw new ValidationException(sprintf('The value of field "%s" of type "%s" is not an integral multiple of the step value %s', $field->name(), $field->type(), $field->step()), 'stepMismatch', ['step' => $field->step()]);
                    }
                }

                return $value;
            },
        ],
    ];
};
