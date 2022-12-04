<?php

use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Formwork;
use Formwork\Utils\Constraint;
use Formwork\Utils\Date;
use Formwork\Utils\Str;

return [
    'format' => function (Field $field, string $format = null, string $type = 'pattern'): string {
        $format = match (strtolower($type)) {
            'pattern' => Date::patternToFormat($format),
            'date'    => $format
        };
        return $field->isEmpty() ? '' : Date::formatTimestamp($field->toTimestamp(), $format);
    },

    'toTimestamp' => function (Field $field): ?int {
        return $field->isEmpty() ? null : Date::toTimestamp($field->value());
    },

    'toDuration' => function (Field $field): string {
        return $field->isEmpty() ? '' : Date::formatTimestampAsDistance($field->toTimestamp(), Formwork::instance()->languages()->current());
    },

    'toString' => function (Field $field): string {
        return $field->isEmpty() ? '' : $field->format();
    },

    'return' => function (Field $field): Field {
        return $field;
    },

    'validate' => function (Field $field, $value): ?string {
        if (Constraint::isEmpty($value)) {
            return null;
        }

        try {
            return date('Y-m-d H:i:s', Date::toTimestamp($value));
        } catch (InvalidArgumentException $e) {
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s":%s', $field->name(), $field->type(), Str::after($e->getMessage(), ':')));
        }
    }
];
