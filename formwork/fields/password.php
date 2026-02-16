<?php

use Formwork\Cms\App;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'extend' => 'text',

        'methods' => [
            'ignoreEmpty' => function (Field $field) {
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
