<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class AssociativeCollection extends Collection
{
    /**
     * Get data by key returning a default value if key is not present
     *
     * @param string     $key
     * @param mixed|null $default
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Return whether a key is present
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }
}
