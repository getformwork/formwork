<?php

namespace Formwork\Data\Traits;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
trait DataMultipleSetter
{
    /** @use DataSetter<TData> */
    use DataSetter;

    /**
     * Set multiple values
     *
     * @param array<key-of<TData>, value-of<TData>> $keysAndValues
     */
    public function setMultiple(array $keysAndValues): void
    {
        foreach ($keysAndValues as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Remove multiple values
     *
     * @param list<key-of<TData>> $keys
     */
    public function removeMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }
}
