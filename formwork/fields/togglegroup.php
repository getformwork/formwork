<?php

use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return [
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
    }
];
