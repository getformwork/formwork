<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class DataGetter
{
    /**
     * Array containing data
     */
    protected array $data = [];

    /**
     * Create a new instance
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Return whether multiple keys are present
     */
    public function hasMultiple(array $key): bool
    {
        foreach ($key as $k) {
            if (!$this->has($k)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return whether data is present
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Return an array containing data
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Create an instance from another getter
     */
    public static function fromGetter(DataGetter $getter): self
    {
        return new static($getter->data);
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
