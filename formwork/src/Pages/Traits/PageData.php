<?php

namespace Formwork\Pages\Traits;

use BadMethodCallException;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Utils\Arr;

trait PageData
{
    use DataMultipleGetter;
    use DataMultipleSetter;

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        return property_exists($this, $key) || $this->fields->has($key) || Arr::has($this->data, $key);
    }

    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, $default = null)
    {
        // Get values from property
        if (property_exists($this, $key)) {
            // Call getter method if exists. We check property existence before
            // to avoid using get to call methods arbitrarily
            if (method_exists($this, $key)) {
                return $this->$key();
            }

            return $this->$key;
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
    public function set(string $key, $value): void
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        } else {
            Arr::set($this->data, $key, $value);
        }
    }

    /**
     * Return page data as array
     */
    public function toArray(): array
    {
        $properties = array_keys(get_class_vars($this::class));

        Arr::pull($properties, 'data');

        $data = array_merge($this->data, $this->getMultiple($properties));

        ksort($data);

        return $data;
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }
}
