<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return [
    'return' => function (Field $field): Field {
        return $field;
    },

    'validate' => function (Field $field, $value): ?string {
        if (Constraint::isEmpty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        return $value;
    }
];
