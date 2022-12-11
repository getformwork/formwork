<?php

namespace Formwork\Fields;

use Formwork\Data\AbstractCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Utils\Arr;

class FieldCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Field::class;

    /**
     * Fields layout
     */
    protected Layout $layout;

    /**
     * Create a new FieldCollection instance
     *
     * @param array $fields Array of Field objects
     */
    public function __construct(array $fields, Layout $layout)
    {
        parent::__construct(Arr::map($fields, fn ($data, $name) => new Field($name, $data, $this)));

        $this->layout = $layout;
    }

    /**
     * Return fields layout
     */
    public function layout(): Layout
    {
        return $this->layout;
    }

    /**
     * @inheritdoc
     */
    public function pluck(string $key, $default = null): array
    {
        return $this->everyItem()->get($key, $default)->toArray();
    }

    /**
     * Validate every field in the collection
     */
    public function validate(): static
    {
        $this->everyItem()->validate();
        return $this;
    }

    /**
     * Return whether every field in the collection is valid
     */
    public function isValid(): bool
    {
        return $this->every(fn ($field) => $field->isValid());
    }

    /**
     * Return whether every field in the collection has been validated
     */
    public function isValidated(): bool
    {
        return $this->every(fn ($field) => $field->isValidated());
    }

    /**
     * Set field values from the given data
     */
    public function setValues($data): static
    {
        $data = Arr::from($data);

        foreach ($this as $field) {
            if (Arr::has($data, $field->name())) {
                $field->set('value', Arr::get($data, $field->name()));
            }
        }

        return $this;
    }
}
