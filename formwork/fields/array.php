<?php

use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;

return [
    'validate' => function (Field $field, $value) {
        if (Constraint::isEmpty($value)) {
            return [];
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        if ($field->is('associative')) {
            foreach (array_keys($value) as $key) {
                if (is_int($key)) {
                    unset($value[$key]);
                }
            }
        }

        return array_filter($value);
    },
];
