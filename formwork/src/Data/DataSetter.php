<?php

namespace Formwork\Data;

class DataSetter extends DataGetter
{
    /**
     * Set a data value by key
     */
    public function set(string $key, $value): void
    {
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
