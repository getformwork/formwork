<?php

namespace Formwork\Cache;

abstract class AbstractCache
{
    /**
     * Return cached resource
     */
    abstract public function fetch(string $key);

    /**
     * Save data to cache
     */
    abstract public function save(string $key, $value);

    /**
     * Delete cached resource
     */
    abstract public function delete(string $key);

    /**
     * Clear cache
     */
    abstract public function clear();

    /**
     * Return whether a resource is cached
     *
     * @return bool
     */
    abstract public function has(string $key);

    /**
     * Fetch multiple data from cache
     *
     * @return array
     */
    public function fetchMultiple(array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->fetch($key);
        }
        return $result;
    }

    /**
     * Save multiple data to cache
     */
    public function saveMultiple(array $keysAndValues)
    {
        foreach ($keysAndValues as $key => $value) {
            $this->save($key, $value);
        }
    }

    /**
     * Delete multiple cached resources
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    /**
     * Return whether multiple resources are cached
     *
     * @return bool
     */
    public function hasMultiple(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }
}
