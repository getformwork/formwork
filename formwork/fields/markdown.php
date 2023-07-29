<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Pages\Site;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Constraint;
use Formwork\Utils\Str;

return function (Site $site) {
    return [
        'toHTML' => function (Field $field) use ($site): string {
            $currentPage = $site->currentPage();
            return Markdown::parse((string) $field->value(), ['baseRoute' => $currentPage ? $currentPage->route() : '/']);
        },

        'toString' => function (Field $field): string {
            return $field->toHTML();
        },

        'toPlainText' => function (Field $field): string {
            return Str::removeHTML($field->toHTML());
        },

        'return' => function (Field $field): Field {
            return $field;
        },

        'validate' => function (Field $field, $value): string {
            if (Constraint::isEmpty($value)) {
                return '';
            }

            if (!is_string($value) && !is_numeric($value)) {
                throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
            }

            if ($field->has('min') && strlen($value) < $field->get('min')) {
                throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('min')));
            }

            if ($field->has('max') && strlen($value) > $field->get('max')) {
                throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('max')));
            }

            $value = str_replace("\r\n", "\n", $value);

            return $value;
        },
    ];

};
