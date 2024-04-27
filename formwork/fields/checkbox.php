<?php

use Formwork\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;

use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'validate' => function (Field $field, $value) {
            if (Constraint::isTruthy($value)) {
                return true;
            }

            if (Constraint::isFalsy($value)) {
                return false;
            }

            if ($value === null) {
                return false;
            }

            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        },
    ];
};
