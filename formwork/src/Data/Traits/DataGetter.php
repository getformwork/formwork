<?php

namespace Formwork\Data\Traits;

use Formwork\Utils\Arr;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
trait DataGetter
{
    use DataAccessors;

    /**
     * @var TData
     */
    protected array $data = [];

    /**
     * Return whether a key is present
     *
     * @param key-of<TData> $key
     */
    public function has(string $key): bool
    {
        return isset($this->dataGetters()[$key])
            || Arr::has($this->data, $key);
    }

    /**
     * Get data by key returning a default value if key is not present
     *
     * @template TKey of key-of<TData>
     * @template TDefault
     *
     * @param TKey     $key
     * @param TDefault $default
     *
     * @return TDefault|value-of<TData>
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($getter = $this->dataGetters()[$key] ?? null) {
            return match ($getter['type']) {
                'property' => $this->{$getter['name']},
                'method'   => $this->{$getter['name']}(),
            };
        }

        return Arr::get($this->data, $key, $default);
    }
}
