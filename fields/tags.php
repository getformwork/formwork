<?php

use Formwork\Cms\App;
use Formwork\Data\Collection;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'default' => [],

        'methods' => [
            'toString' => function ($field) {
                return implode(', ', $field->value() ?? []);
            },

            'return' => function (Field $field): Collection {
                return Collection::from($field->value() ?? []);
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): array {
                if (Constraint::isEmpty($value)) {
                    return [];
                }

                if (is_string($value)) {
                    $value = array_map(trim(...), explode(',', $value));
                }

                if (!is_array($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                if ($field->has('pattern')) {
                    $value = array_filter($value, static fn($item): bool => Constraint::matchesRegex($item, $field->get('pattern')));
                }

                if ($field->limit() !== null && count($value) > $field->limit()) {
                    throw new ValidationException(sprintf('Field "%s" of type "%s" has a limit of %d items', $field->name(), $field->type(), $field->get('limit')), 'tooManyItems', ['limit' => $field->get('limit')]);
                }

                return array_values(array_filter($value));
            },

            /**
             * Get the field dropdown options
             */
            'options' => function ($field): ?array {
                $options = $field->get('options', null);

                return $options !== null ? Arr::from($options) : null;
            },

            /**
             * Return whether the field accepts dropdown options
             */
            'accept' => function ($field): string {
                return $field->get('accept', 'options');
            },

            /**
             * Return the maximum number of tags allowed in the field
             */
            'limit' => function ($field): ?int {
                return $field->get('limit', null);
            },

            /**
             * Return whether the field tags are orderable
             */
            'isOrderable' => function ($field): bool {
                return $field->is('orderable', true);
            },
        ],
    ];
};
