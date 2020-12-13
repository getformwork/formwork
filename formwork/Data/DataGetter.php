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
        if (!is_string($key)) {
            trigger_error('Using ' . static::class . '::get() with a non-string $key argument is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
            $key = (string) $key;
        }
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Return whether a key is present
     *
     * @param array|string $key
     */
    public function has($key): bool
    {
        if (is_array($key)) {
            trigger_error('Using ' . static::class . '::has() with an array as $key argument is deprecated since Formwork 1.10.0, use ' . static::class . '::hasMultiple() instead', E_USER_DEPRECATED);
            return $this->hasMultiple($key);
        }
        if (!is_string($key)) {
            trigger_error('Using ' . static::class . '::has() with a non-string $key argument is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
            $key = (string) $key;
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
