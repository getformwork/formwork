<?php

use Formwork\Cms\App;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\Str;

return function (App $app, FieldFactory $fieldFactory): array {
    return [
        'methods' => [
            /**
             * Return whether the field is associative
             */
            'isAssociative' => function (Field $field): bool {
                return $field->is('associative', false);
            },

            /**
             * Return whether the field allows empty values
             *
             * @since 2.2.0
             */
            'allowEmptyValues' => function (Field $field): bool {
                return $field->is('allowEmptyValues', false);
            },

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): array {
                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                }

                if (Constraint::isEmpty($value)) {
                    return [];
                }

                if (!is_array($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                if (!$field->isAssociative() || !$field->allowEmptyValues()) {
                    return Arr::reject($value, fn($v) => Constraint::isEmpty($v));
                }

                return $value;
            },

            /**
             * Return the fields for each item in the array
             *
             * @since 2.2.0
             */
            'items' => function (Field $field, array $default = []) use ($fieldFactory): FieldCollection {
                $fields = new FieldCollection();
                $fields->setModel($field->parent()?->model());

                foreach ($field->value() ?: $default as $key => $value) {
                    $fieldName = Str::slug("{$field->name()}-{$key}");
                    $formKey = $field->isAssociative() ? $key : '';

                    $valueField = $fieldFactory->make($fieldName, [
                        ...$field->get('items', ['type' => 'text']),
                        'formName'    => "{$field->formName()}[{$formKey}]",
                        'itemKey'     => $key,
                        'placeholder' => $field->get('placeholderValue'),
                        'value'       => $value,
                    ], $fields);

                    $fields->set($fieldName, $valueField);
                }

                return $fields->validate();
            },

            'return' => function (Field $field) {
                return $field->items()->keyBy('itemKey')->toArray();
            },
        ],
    ];
};
