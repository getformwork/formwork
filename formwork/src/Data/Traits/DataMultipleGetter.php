<?php

namespace Formwork\Data\Traits;

trait DataMultipleGetter
{
    use DataGetter;

    /**
     * Return whether multiple keys are present
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
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }
}
