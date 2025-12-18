<?php

namespace Formwork\Model;

use BadMethodCallException;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Arr;
use ReflectionAttribute;
use ReflectionProperty;

class Model implements Arrayable
{
    use DataMultipleGetter;
    use DataMultipleSetter;

    /**
     * Identifier used to reference the model
     */
    protected const string MODEL_IDENTIFIER = 'model';

    /**
     * Model scheme
     */
    protected Scheme $scheme;

    /**
     * Model fields
     */
    protected FieldCollection $fields;

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    /**
     * Return the model identifier
     */
    public function getModelIdentifier(): string
    {
        return static::MODEL_IDENTIFIER;
    }

    /**
     * Return the model scheme
     */
    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    /**
     * Return the model fields
     */
    public function fields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            return true;
        }
        if ($this->fields->has($key)) {
            return true;
        }
        return Arr::has($this->data, $key);
    }

    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Get values from property
        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            // Call getter method if exists. We check property existence before
            // to avoid using get to call methods arbitrarily
            if (method_exists($this, $key)) {
                return $this->{$key}();
            }

            return $this->{$key} ?? $default;
        }

        // Get values from fields
        if ($this->fields->has($key)) {
            $field = $this->fields->get($key);

            // If defined use the value returned by `return()`
            if ($field->hasMethod('return')) {
                return $field->return();
            }

            return $field->value();
        }

        // Get values from data
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Set a data value by key
     */
    public function set(string $key, mixed $value): void
    {
        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            if ($this->isReadonly($key)) {
                throw new BadMethodCallException(sprintf('Cannot set readonly model property %s::$%s', static::class, $key));
            }

            // If defined use a setter
            if (method_exists($this, $setter = 'set' . ucfirst($key))) {
                $this->{$setter}($value);
                return;
            }

            $this->{$key} = $value;
            return;
        }

        Arr::set($this->data, $key, $value);

        // Set value in the corresponding field if exists
        if (isset($this->fields) && $this->fields->has($key)) {
            /** @var Field */
            $field = $this->fields->get($key);
            $field->set('value', $value);
            $field->validate();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $properties = array_keys(get_class_vars(static::class));

        Arr::pull($properties, 'data');

        /** @var list<string> $properties */
        $data = [...$this->data, ...$this->getMultiple($properties)];

        ksort($data);

        return $data;
    }

    /**
     * Return the model data
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Return whether a property has the `ReadonlyModelProperty` attribute
     */
    private function isReadonly(string $property): bool
    {
        $attributes = (new ReflectionProperty($this, $property))->getAttributes(ReadonlyModelProperty::class, ReflectionAttribute::IS_INSTANCEOF);
        return $attributes !== [];
    }
}
