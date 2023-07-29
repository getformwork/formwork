<?php

use Formwork\App;
use Formwork\Fields\Field;

use Formwork\Utils\Arr;

return function (App $app) {
    return [
        'options' => function (Field $field) {
            return Arr::from($field->get('options', []));
        },

        'validate' => function (Field $field, $value) {
            if (is_numeric($value)) {
                // This reliably casts numeric values to int or float
                return $value + 0;
            }

            return $value;
        },
    ];
};
