<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): string {
                if (Constraint::isEmpty($value)) {
                    return '';
                }

                if (!is_string($value) && !is_numeric($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                if ($field->has('min') && strlen((string) $value) < $field->get('min')) {
                    throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('min')));
                }

                if ($field->has('max') && strlen((string) $value) > $field->get('max')) {
                    throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('max')));
                }

                if ($field->has('pattern') && !Constraint::matchesRegex((string) $value, $field->get('pattern'))) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not match the required pattern', $field->name(), $field->value()));
                }

                if (!$field->hasUniqueValue()) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be unique', $field->name(), $field->value()), 'alreadyExists');
                }

                return (string) $value;
            },

            /**
             * Get the source field from which this field derives its value
             */
            'source' => function (Field $field): ?Field {
                if (($source = $field->get('source')) === null) {
                    return null;
                }
                return $field->parent()?->get($source);
            },

            /**
             * Return whether the field is set to auto-update its value
             */
            'autoUpdate' => function (Field $field): bool {
                return $field->is('autoUpdate', true);
            },

            /**
             * Check if the field has a unique value among the slugs of the sibling pages
             */
            'hasUniqueValue' => function (Field $field): bool {
                $root = $field->get('root');

                if ($root === null) {
                    return true;
                }

                $parentField = $field->parent()?->get($root);

                if ($parentField === null || $parentField->type() !== 'page') {
                    throw new ValidationException(sprintf('Invalid parent reference for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                $children = $parentField->return()->children();

                foreach ($children as $child) {
                    if ($child->slug() === $field->value()) {
                        return false;
                    }
                }

                return true;
            },
        ],
    ];
};
