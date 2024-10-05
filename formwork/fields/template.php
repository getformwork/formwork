<?php

use Formwork\Fields\Field;
use Formwork\Site;

return function (Site $site) {
    return [
        'return' => function (Field $field) use ($site) {
            return $site->templates()->get($field->value());
        },

        'validate' => function (Field $field, $value) {
            if ($value === '') {
                return null;
            }

            return $value;
        },
    ];
};
