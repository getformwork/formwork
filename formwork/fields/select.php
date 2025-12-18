<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Get the field dropdown options
             */
            'options' => function (Field $field) {
                return Arr::from($field->get('options', []));
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value) {
                if (Constraint::isEmpty($value)) {
                    return '';
                }

                if (!array_key_exists($value, $field->options())) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
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
