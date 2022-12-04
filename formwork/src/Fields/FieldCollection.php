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
        parent::__construct(Arr::map($fields, fn ($data, $name) => new Field($name, $data)));

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
     * Validate fields against data
     */
    public function validate($data): self
    {
        $data = Arr::from($data);

        foreach ($this->data as $name => $field) {
            $field->set('value', Arr::get($data, $name));
            $field->validate();
        }

        return $this;
    }
}
