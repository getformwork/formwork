<?php

namespace Formwork\Pages\Traits;

use BadMethodCallException;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Utils\Arr;
use ReflectionProperty;

trait PageData
{
    use DataMultipleGetter;
    use DataMultipleSetter;

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
            // If defined use a setter
            if (method_exists($this, $setter = 'set' . ucfirst($key))) {
                $this->{$setter}($value);
                return;
            }

            $this->{$key} = $value;
            return;
        }

        Arr::set($this->data, $key, $value);
    }

    /**
     * Return page data as array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $properties = array_keys(get_class_vars($this::class));

        Arr::pull($properties, 'data');

        $data = [...$this->data, ...$this->getMultiple($properties)];

        ksort($data);

        return $data;
    }

    /**
     * Get page data
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
