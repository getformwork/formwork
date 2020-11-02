<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class AssociativeCollection extends Collection
{
    /**
     * Get data by key returning a default value if key is not present
     *
     * @param mixed|null $default
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Return whether a key is present
     *
     * @param string $key
     */
    public function has($key): bool
    {
        if (!is_string($key)) {
            trigger_error('Using ' . static::class . '::has() with a non-string $key parameter is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
            $key = (string) $key;
        }
        return Arr::has($this->items, $key);
    }
}
