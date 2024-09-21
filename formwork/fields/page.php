<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Pages\Page;
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

        'setValue' => function (Field $field, $value) use ($site): ?string {
            if ($value === $site) {
                return '.';
            }

            if ($value instanceof Page) {
                return $value->route();
            }

            return $value;
        },

        'validate' => function (Field $field, $value) {
            if ($value === '') {
                return null;
            }

            if ($value === '.' && !$field->get('allowSite', false)) {
                throw new ValidationException('Invalid Site');
            }

            return $value;
        },
    ];
};
