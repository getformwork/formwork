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
    abstract public function save(string $key, $value): void;

    /**
     * Delete cached resource
     */
    abstract public function delete(string $key): void;

    /**
     * Clear cache
     */
    abstract public function clear(): void;

    /**
     * Return whether a resource is cached
     */
    abstract public function has(string $key): bool;

    /**
     * Fetch multiple data from cache
     */
    public function fetchMultiple(array $keys): array
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
    public function saveMultiple(array $keysAndValues): void
    {
        foreach ($keysAndValues as $key => $value) {
            $this->save($key, $value);
        }
    }

    /**
     * Delete multiple cached resources
     */
    public function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    /**
     * Return whether multiple resources are cached
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
}
