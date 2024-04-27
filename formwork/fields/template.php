<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Pages\Site;

return function (Site $site) {
    return [
        'return' => function (Field $field) use ($site) {
            return $site->templates()->get($field->value());
        },

        'validate' => function (Field $field, $value) {
            if ($value === '') {
                if ($field->isRequired()) {
                    throw new ValidationException('Invalid empty field');
                }
                return null;
            }

            return $value;
        },
    ];
};
