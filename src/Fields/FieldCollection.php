<?php

namespace Formwork\Fields;

use Formwork\Data\AbstractCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Http\Request;
use Formwork\Model\Model;
use Formwork\Utils\Arr;

/**
 * @extends AbstractCollection<Field>
 */
class FieldCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Field::class;

    protected bool $mutable = true;

    /**
     * Fields layout
     */
    protected Layout $layout;

    /**
     * Fields model
     */
    protected ?Model $model = null;

    /**
     * Set fields layout
     */
    public function setLayout(Layout $layout): void
    {
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
     * Set fields model
     */
    public function setModel(?Model $model): void
    {
        $this->model = $model;
    }

    /**
     * Return fields model
     */
    public function model(): ?Model
    {
        return $this->model;
    }

    public function extract(string $key, mixed $default = null): array
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
        $valid = true;

        // Iterate all fields to ensure all validations are run
        foreach ($this as $field) {
            if (!$field->isValid()) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Return whether every field in the collection has been validated
     */
    public function isValidated(): bool
    {
        return $this->every(fn($field) => $field->isValidated());
    }

    /**
     * Set field values from the given data
     *
     * If the `$default` argument is given, it will be used instead of missing data
     */
    public function setValues(mixed $data, mixed $default = null): static
    {
        $data = Arr::from($data);

        foreach ($this as $field) {
            if (Arr::has($data, $field->name())) {
                $field->set('value', Arr::get($data, $field->name()));
            } elseif (func_num_args() === 2) {
                $field->set('value', $default);
            }
        }

        return $this;
    }

    /**
     * Set field values from a Request
     */
    public function setValuesFromRequest(Request $request, mixed $default = null): static
    {
        return $this->setValues(Arr::extend(
            $request->query()->toArray(),
            $request->input()->toArray(),
            $request->files()->toArray()
        ), $default);
    }
}
