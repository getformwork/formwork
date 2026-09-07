<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): ?string {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                if (!Constraint::matchesRegex($value, '#[0-9A-Fa-f]{6}')) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                return strtolower($value);
            },
        ],
    ];
};
