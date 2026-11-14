<?php

use Formwork\Cms\Site;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (Site $site) {
    return [
        'methods' => [
            'return' => function (Field $field) use ($site) {
                return $site->templates()->get($field->value());
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value) use ($site) {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                if (!is_string($value) || !$site->templates()->has($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                return $value;
            },
        ],
    ];
};
