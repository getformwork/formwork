<?php

use Formwork\Cms\App;
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
