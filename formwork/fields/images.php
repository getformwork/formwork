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
             * Get the collection of images associated with the field
             */
            'getImages' => function (Field $field): FileCollection {
                if (!$field->has('options')) {
                    $model = $field->parent()?->model();

                    if ($model === null || !method_exists($model, 'files')) {
                        throw new InvalidValueException(sprintf('Field "%s" of type "%s" must have a model with files', $field->name(), $field->type()));
                    }

                    $files = $model->files();
                } else {
                    $files = $field->get('options');
                }

                return $files->filter(static fn(File $file) => $file instanceof Image);
            },

            'toString' => function ($field) {
                return implode(', ', $field->value() ?? []);
            },

            'return' => function (Field $field): FileCollection {
                return $field->getImages()->filter(static fn(File $file) => in_array($file->name(), $field->value(), true));
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
                return $field->getImages()
                    ->map(static fn(Image $image) => [
                        'value' => $image->name(),
                        'icon'  => 'image',
                        'thumb' => $image->square(300, 'contain')->uri(),
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
