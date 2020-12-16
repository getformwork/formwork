<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class AssociativeCollection extends Collection
{
    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }
}
