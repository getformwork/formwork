<?php

namespace Formwork\Data\Traits;

trait DataMultipleSetter
{
    use DataSetter;

    /**
     * Set multiple values
     */
    public function setMultiple(array $keysAndValues): void
    {
        foreach ($keysAndValues as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Remove multiple values
     */
    public function removeMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }
}
