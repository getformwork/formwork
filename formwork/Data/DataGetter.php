<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class DataGetter
{
    /**
     * Array containing data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new instance
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get data by key returning a default value if key is not present
     *
     * @param string     $key
     * @param mixed|null $default
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Return whether a key or multiple keys are present
     *
     * @param array|string $key
     */
    public function has($key): bool
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if (!$this->has($k)) {
                    return false;
                }
            }
            return true;
        }
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
     * Return an array containing data
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
