<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Pages\PageCollection;
use Formwork\Pages\Site;

return function (Site $site) {
    return [
        'return' => function (Field $field) use ($site) {
            if ($field->value() === '.' && $field->get('allowSite', false)) {
                return $site;
            }
            return $site->findPage($field->value() ?? '');
        },

        'collection' => function (Field $field) use ($site): PageCollection {
            return $field->get('collection', $site->descendants());
        },

        'validate' => function (Field $field, $value) {
            if ($value === '') {
                if ($field->isRequired()) {
                    throw new ValidationException('Invalid empty field');
                }
                return null;
            }

            if ($value === '.' && !$field->get('allowSite', false)) {
                throw new ValidationException('Invalid Site');
            }

            return $value;
        },
    ];
};
