<?php

namespace Formwork\Cache;

use InvalidArgumentException;

class CacheManager
{
    /**
     * @param array<string, NamespacedCacheInterface> $caches
     */
    public function __construct(protected array $caches = []) {}

    /**
     * Get a cache instance by its namespace
     *
     * @throws InvalidArgumentException
     */
    public function get(string $namespace): NamespacedCacheInterface
    {
        return $this->caches[$namespace] ?? throw new InvalidArgumentException(sprintf('Cache "%s" does not exist', $namespace));
    }

    /**
     * Return whether a cache instance exists for the given namespace
     */
    public function has(string $namespace): bool
    {
        return isset($this->caches[$namespace]);
    }

    /**
     * Add a cache instance to the manager
     */
    public function add(NamespacedCacheInterface $namespacedCache): void
    {
        $this->caches[$namespacedCache->namespace()] = $namespacedCache;
    }

    /**
     * Get multiple cache instances by their namespaces
     *
     * @param list<string> $namespaces
     *
     * @return array<string, NamespacedCacheInterface>
     */
    public function getMultiple(array $namespaces): array
    {
        $caches = [];
        foreach ($namespaces as $namespace) {
            $caches[$namespace] = $this->get($namespace);
        }
        return $caches;
    }

    /**
     * Get all cache instances
     *
     * @return array<string, NamespacedCacheInterface>
     */
    public function getAll(): array
    {
        return $this->getMultiple(array_keys($this->caches));
    }
}
