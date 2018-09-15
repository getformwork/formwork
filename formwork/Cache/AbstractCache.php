<?php

namespace Formwork\Cache;

abstract class AbstractCache
{
    abstract public function fetch($key);

    abstract public function save($key, $value);

    abstract public function delete($key);

    abstract public function has($key);

    public function fetchMultiple($keys)
    {
        $result = array();
        foreach ($keys as $key) {
            $result[] = $this->fetch($key);
        }
        return $result;
    }

    public function saveMultiple($keysAndValues)
    {
        foreach ($keysAndValues as $key => $value) {
            $this->save($key, $value);
        }
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function hasMultiple($keys)
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }
}
