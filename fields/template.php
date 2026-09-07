<?php

use Formwork\Cms\Site;
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
            'validate' => function (Field $field, $value) {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                return $value;
            },
        ],
    ];
};
