<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'default' => '',

        'methods' => [
            /**
             * Return the minimum allowed length for the field
             *
             * If not set, no minimum length is enforced
             */
            'minLength' => function (Field $field): ?int {
                return $field->get('minLength');
            },

            /**
             * Return the maximum allowed length for the field
             *
             * If not set, no maximum length is enforced
             */
            'maxLength' => function (Field $field): ?int {
                return $field->get('maxLength');
            },

            /**
             * Return the rows displayed by default in the textarea
             *
             * If not set, the default is 5 rows
             */
            'rows' => function (Field $field): int {
                return $field->get('rows', 5);
            },

            /**
             * Return whether the entered text can be autocompleted by the browser
             *
             * By default, autocomplete is `false`, meaning that the browser will not suggest previously entered values
             */
            'autocomplete' => function (Field $field): bool {
                return $field->is('autocomplete');
            },

            /**
             * Return whether the field should be spellchecked by the browser
             *
             * By default, spellcheck is `false`, meaning that the browser will not check the spelling of the entered text
             */
            'spellcheck' => function (Field $field): bool {
                return $field->is('spellcheck', true);
            },

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

                if ($field->has('min') && strlen((string) $value) < $field->minLength()) {
                    throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->type(), $field->minLength()), 'valueTooShort', ['minLength' => $field->minLength()]);
                }

                if ($field->has('max') && strlen((string) $value) > $field->maxLength()) {
                    throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->type(), $field->maxLength()), 'valueTooLong', ['maxLength' => $field->maxLength()]);
                }

                return str_replace("\r\n", "\n", (string) $value);
            },
        ],
    ];
};
