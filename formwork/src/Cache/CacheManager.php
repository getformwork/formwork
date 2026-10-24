<?php

namespace Formwork\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

class CacheManager
{
    /**
     * Regex matching valid cache names
     *
     * @since 2.4.0
     */
    public const string NAME_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

    /**
     * Names reserved for the cache types handled by the panel and the command line
     *
     * @since 2.4.0
     */
    public const array RESERVED_NAMES = ['all', 'config', 'default', 'images'];

    /**
     * Cache instances by name
     *
     * @var array<non-empty-string, CacheInterface>
     */
    protected array $caches = [];

    /**
     * @param array<non-empty-string, CacheInterface> $caches Cache instances by name
     *
     * @throws InvalidArgumentException If a name is not valid or is reserved
     */
    public function __construct(array $caches = [])
    {
        foreach ($caches as $name => $cache) {
            $this->add((string) $name, $cache);
        }
    }

    /**
     * Get a cache instance by its name
     *
     * @param non-empty-string $name
     *
     * @throws InvalidArgumentException If the cache does not exist
     */
    public function get(string $name): CacheInterface
    {
        return $this->caches[$name] ?? throw new InvalidArgumentException(sprintf('Cache "%s" does not exist', $name));
    }

    /**
     * Return whether a cache instance exists with the given name
     *
     * @param non-empty-string $name
     */
    public function has(string $name): bool
    {
        return isset($this->caches[$name]);
    }

    /**
     * Add a cache instance to the manager with the given name
     *
     * @throws InvalidArgumentException If the name is not valid or is reserved
     */
    public function add(string $name, CacheInterface $cache): void
    {
        if (!preg_match(self::NAME_REGEX, $name)) {
            throw new InvalidArgumentException(sprintf('Cache name "%s" is not valid', $name));
        }

        if (in_array($name, self::RESERVED_NAMES, true)) {
            throw new InvalidArgumentException(sprintf('Cache name "%s" is reserved', $name));
        }

        $this->caches[$name] = $cache;
    }

    /**
     * Get multiple cache instances by their names
     *
     * @param list<non-empty-string> $names
     *
     * @throws InvalidArgumentException If any of the caches does not exist
     *
     * @return array<non-empty-string, CacheInterface>
     */
    public function getMultiple(array $names): array
    {
        $caches = [];
        foreach ($names as $name) {
            $caches[$name] = $this->get($name);
        }
        return $caches;
    }

    /**
     * Get all cache instances by name
     *
     * @return array<non-empty-string, CacheInterface>
     */
    public function getAll(): array
    {
        return $this->caches;
    }
}
