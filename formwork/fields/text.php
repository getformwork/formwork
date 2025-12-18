<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
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
             * Return the pattern that the field value must match
             *
             * This is a regular expression that the value must match.
             * If not set, no pattern validation is performed.
             */
            'pattern' => function (Field $field): ?string {
                return $field->get('pattern');
            },

            /**
             * Return the autocomplete attribute for the field
             *
             * This is used to specify the type of data that the field represents,
             * which can help browsers to provide better autocomplete suggestions.
             * If not set, no autocomplete attribute is added.
             */
            'autocomplete' => function (Field $field): ?string {
                return $field->get('autocomplete');
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

                if ($field->has('minLength') && strlen((string) $value) < $field->minLength()) {
                    throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->minLength()));
                }

                if ($field->has('maxLength') && strlen((string) $value) > $field->maxLength()) {
                    throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->maxLength()));
                }

                if ($field->has('pattern') && !Constraint::matchesRegex((string) $value, $field->pattern())) {
                    throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not match the required pattern', $field->name(), $field->value()));
                }

                return (string) $value;
            },
        ],
    ];
};
