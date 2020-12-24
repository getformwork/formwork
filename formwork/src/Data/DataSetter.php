<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;

class DataSetter extends DataGetter
{
    /**
     * Set a data value by key
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Remove a data value by key
     */
    public function remove(string $key): void
    {
        Arr::remove($this->data, $key);
    }
}
