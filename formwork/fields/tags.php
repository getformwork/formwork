<?php

use Formwork\Data\Collection;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return [
    'toString' => function ($field) {
        return implode(', ', $field->value() ?? []);
    },

    'return' => function (Field $field): Collection {
        return Collection::from($field->value() ?? []);
    },

    'validate' => function (Field $field, $value): array {
        if (Constraint::isEmpty($value)) {
            return [];
        }

        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        if ($field->has('pattern')) {
            $value = array_filter($value, static fn ($item): bool => Constraint::matchesRegex($item, $field->get('pattern')));
        }

        return array_values(array_filter($value));
    },
];
