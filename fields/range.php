<?php

use Formwork\Cms\App;
use Formwork\Fields\Field;

return function (App $app) {
    return [
        'extend'  => 'number',
        'methods' => [
            /**
             * Return whether the field should display ticks
             */
            'ticks' => function (Field $field): bool {
                return $field->is('ticks', false);
            },
        ],
    ];
};
