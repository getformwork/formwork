<?php

use Formwork\Fields\Field;

return [
    'validate' => function (Field $field, $value) {
        if (is_numeric($value)) {
            // This reliably casts numeric values to int or float
            return $value + 0;
        }

        return $value;
    },
];
