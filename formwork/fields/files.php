<?php

use Formwork\Cms\App;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Images\Image;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Get the collection of files associated with the field
             */
            'getFiles' => function (Field $field): FileCollection {
                if (!$field->has('options')) {
                    $model = $field->parent()?->model();

                    if ($model === null || !method_exists($model, 'files')) {
                        throw new InvalidValueException(sprintf('Field "%s" of type "%s" must have a model with files', $field->name(), $field->type()));
                    }

                    return $model->files();
                }

                return $field->get('options');
            },

            'toString' => function ($field) {
                return implode(', ', $field->value() ?? []);
            },

            'return' => function (Field $field): FileCollection {
                return $field->getFiles()->filter(static fn(File $file) => in_array($file->name(), $field->value(), true));
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
                    throw new ValidationException(sprintf('Field "%s" of type "%s" has a limit of %d items', $field->name(), $field->type(), $field->get('limit')));
                }

                return array_values(array_filter($value));
            },

            /**
             * Get the field dropdown options
             */
            'options' => function (Field $field): array {
                $collection = $field->getFiles();

                if ($field->has('fileType')) {
                    $collection = $collection->filter(static fn(File $file) => in_array($file->type(), (array) $field->get('fileType'), true));
                }

                return $collection
                    ->map(static fn(File $file) => [
                        'value' => $file->name(),
                        'icon'  => 'file-' . $file->type(),
                        'thumb' => $file instanceof Image ? $file->square(300, 'contain')->uri() : null,
                    ])->toArray();
            },

            /**
             * Return the maximum number of items allowed in the field
             */
            'limit' => function (Field $field): ?int {
                return $field->get('limit', null);
            },

            /**
             * Return whether the field items are orderable
             */
            'isOrderable' => function ($field): bool {
                return $field->is('orderable', true);
            },
        ],
    ];
};
