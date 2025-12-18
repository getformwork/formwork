<?php

use Formwork\Cms\Site;
use Formwork\Fields\Field;

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
                if ($value === '') {
                    return null;
                }

                return $value;
            },
        ],
    ];
};
