<?php

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'extend' => 'text',

        'methods' => [
            /**
             * Return whether to ignore empty values when setting the field value
             *
             * This is useful for password fields, where you don't want to overwrite the existing password with an empty value if the user doesn't enter a new password.
             *
             * @since 2.3.3
             */
            'ignoreEmpty' => function (Field $field): bool {
                return $field->is('ignoreEmpty', false);
            },

            'setValue' => function (Field $field, $value) {
                if ($field->ignoreEmpty() && Constraint::isEmpty($value)) {
                    return $field->value();
                }
                return $value;
            },
        ],
    ];
};
