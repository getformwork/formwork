<?php

namespace Formwork\Data\Traits;

use Formwork\Utils\Arr;
use LogicException;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
trait DataSetter
{
    use DataAccessors;

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
        if ($setter = $this->dataSetters()[$key] ?? null) {
            match ($setter['type']) {
                'property' => $this->{$setter['name']} = $value,
                'method'   => $this->{$setter['name']}($value),
            };
            return;
        }

        if (isset($this->dataGetters()[$key])) {
            throw new LogicException(sprintf('Cannot set getter-only key %s', $key));
        }

        Arr::set($this->data, $key, $value);
    }

    /**
     * Remove a data value by key
     *
     * @param key-of<TData> $key
     */
    public function remove(string $key): void
    {
        if (isset($this->dataGetters()[$key]) || isset($this->dataSetters()[$key])) {
            throw new LogicException(sprintf('Cannot remove getter- or setter-backed key %s', $key));
        }

        Arr::remove($this->data, $key);
    }
}
