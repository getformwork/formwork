<?php

namespace Formwork\Data\Traits;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
trait DataMultipleGetter
{
    /** @use DataGetter<TData> */
    use DataGetter;

    /**
     * Return whether multiple keys are present
     *
     * @param list<key-of<TData>> $keys
     */
    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get an array containing multiple values
     *
     * @template TKey of key-of<TData>
     * @template TDefault
     *
     * @param list<TKey> $keys
     * @param TDefault   $default
     *
     * @return array<TKey, TDefault|value-of<TData>>
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }
}
