<?php

namespace Formwork\Cache;

use InvalidArgumentException;

class CacheManager
{
    /**
     * @param array<non-empty-string, NamespacedCacheInterface> $caches
     */
    public function __construct(protected array $caches = []) {}

    /**
     * Get a cache instance by its namespace
     *
     * @param non-empty-string $namespace
     *
     * @throws InvalidArgumentException
     */
    public function get(string $namespace): NamespacedCacheInterface
    {
        return $this->caches[$namespace] ?? throw new InvalidArgumentException(sprintf('Cache "%s" does not exist', $namespace));
    }

    /**
     * Return whether a cache instance exists for the given namespace
     *
     * @param non-empty-string $namespace
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
     * @param list<non-empty-string> $namespaces
     *
     * @return array<non-empty-string, NamespacedCacheInterface>
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
     * @return array<non-empty-string, NamespacedCacheInterface>
     */
    public function getAll(): array
    {
        return $this->getMultiple(array_keys($this->caches));
    }
}
