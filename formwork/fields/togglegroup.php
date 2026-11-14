<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Return the field options
             */
            'options' => function (Field $field): array {
                return $field->get('options', []);
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value) {
                if (Constraint::isEmpty($value)) {
                    return $value;
                }

                // Booleans correspond to the integer keys of the options, e.g. `1` and `0`
                $key = is_bool($value) ? (int) $value : $value;

                // Ensure the value is a valid key in the options array
                if ((!is_string($key) && !is_int($key)) || !array_key_exists($key, $field->options())) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                if (Constraint::isTruthy($value)) {
                    return true;
                }

                if (Constraint::isFalsy($value)) {
                    return false;
                }

                if (is_numeric($value)) {
                    // This reliably casts numeric values to int or float
                    return $value + 0;
                }

                return $value;
            },
        ],
    ];
};
