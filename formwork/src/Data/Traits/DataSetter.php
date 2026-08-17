<?php

namespace Formwork\Data\Traits;

use Formwork\Utils\Arr;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
trait DataSetter
{
    /**
     * @var TData
     */
    protected array $data = [];

    /**
     * Set a data value by key
     *
     * @param key-of<TData>   $key
     * @param value-of<TData> $value
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Remove a data value by key
     *
     * @param key-of<TData> $key
     */
    public function remove(string $key): void
    {
        Arr::remove($this->data, $key);
    }
}
