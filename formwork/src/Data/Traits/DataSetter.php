<?php

namespace Formwork\Data\Traits;

use Formwork\Utils\Arr;

trait DataSetter
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Set a data value by key
     */
    public function set(string $key, mixed $value): void
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
