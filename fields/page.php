<?php

use Formwork\Cms\Site;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Utils\Constraint;

return function (Site $site) {
    return [
        'methods' => [
            'return' => function (Field $field) use ($site) {
                if ($field->value() === '.' && $field->get('allowSite', false)) {
                    return $site;
                }
                return $site->findPage($field->value() ?? '');
            },

            /**
             * Return whether the field should allow selecting the Site
             */
            'allowSite' => function (Field $field): bool {
                return $field->is('allowSite', false);
            },

            /**
             * Get the collection of pages associated with the field
             */
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

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value) {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                if ($value === '.' && !$field->get('allowSite', false)) {
                    throw new ValidationException('Invalid Site');
                }

                return $value;
            },
        ],
    ];
};
