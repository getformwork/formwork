<?php

namespace Formwork\Data;

class DataSetter extends DataGetter
{
    /**
     * Set a data value by key
     *
     * @param string $key
     */
    public function set($key, $value): void
    {
        if (!is_string($key)) {
            trigger_error('Using ' . static::class . '::set() with a non-string $key parameter is deprecated since Formwork 1.10.0', E_USER_DEPRECATED);
        }
        $this->data[$key] = $value;
    }

    /**
     * Remove a data value by key
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }
}
