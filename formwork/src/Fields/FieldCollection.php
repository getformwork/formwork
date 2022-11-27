<?php

namespace Formwork\Fields;

use Formwork\Data\AbstractCollection;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Layout\Layout;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;

class FieldCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Field::class;

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

    public function layout(): Layout
    {
        return $this->layout;
    }

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

        foreach ($this->data as $field) {
            $value = Arr::get($data, $field->name());

            // TODO: move to field
            if ($field->isRequired() && Constraint::isEmpty($value)) {
                throw new ValidationException(sprintf('Required field "%s" of type "%s" cannot be empty', $field->name(), $field->type()));
            }

            if ($field->hasMethod('validate')) {
                $value = $field->validate($value);
            }

            $field->set('value', $value);
        }

        return $this;
    }
}
