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
            'return' => function (Field $field): ?File {
                return $field->value() !== null
                    ? $field->getFiles()->get($field->value())
                    : null;
            },

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

            /**
             * Validate the field value
             */
            'validate' => function (Field $field, $value): ?string {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                if (!is_string($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
                }

                return $value;
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
                        'icon'  => "file-{$file->type()}",
                        'thumb' => $file instanceof Image ? $file->square(300, 'contain')->uri() : null,
                    ])->toArray();
            },
        ],
    ];
};
