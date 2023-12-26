<?php

namespace Formwork\Data\Traits;

use Formwork\Utils\Arr;

trait DataGetter
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }
}
