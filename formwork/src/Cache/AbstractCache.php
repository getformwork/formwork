<?php

namespace Formwork\Cache;

use DateInterval;
use DateTimeImmutable;
use Formwork\Cache\Exceptions\InvalidKeyException;

abstract class AbstractCache implements NamespacedCacheInterface
{
    public const string NAMESPACE_REGEX = '/^[a-z0-9]+$/';

    public const string RESERVED_KEY_CHARACTERS = '{}()/\@:';

    /**
     * Get the default time-to-live of cached items
     */
    abstract public function defaultTtl(): int|DateInterval|null;

    /**
     * Fetch cached item
     *
     * @param non-empty-string $key
     *
     * @deprecated since 2.4.0 Use PSR-16 compatible get() method instead
     */
    public function fetch(string $key): mixed
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.4.0. Use PSR-16 compatible get() method instead', __METHOD__), E_USER_DEPRECATED);
        return $this->get($key);
    }

    /**
     * Get cached item
     *
     * @template TDefault of mixed
     *
     * @param non-empty-string $key
     * @param TDefault         $default
     *
     * @throws InvalidKeyException If the key is not valid
     *
     * @return mixed|TDefault
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * Save data to cache
     *
     * @param non-empty-string $key
     *
     * @deprecated since 2.4.0 Use PSR-16 compatible set() method instead
     */
    public function save(string $key, mixed $value, int|DateInterval|null $ttl = null): void
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.4.0. Use PSR-16 compatible set() method instead', __METHOD__), E_USER_DEPRECATED);
        $this->set($key, $value, $ttl);
    }

    /**
     * Set data to cache
     *
     * @param non-empty-string $key
     *
     * @throws InvalidKeyException If the key is not valid
     */
    abstract public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool;

    /**
     * Delete cached item
     *
     * @param non-empty-string $key
     *
     * @throws InvalidKeyException If the key is not valid
     */
    abstract public function delete(string $key): bool;

    /**
     * Clear cache
     */
    abstract public function clear(): bool;

    /**
     * Fetch multiple items from cache
     *
     * @param list<non-empty-string> $keys
     *
     * @return array<non-empty-string, mixed>
     *
     * @deprecated since 2.4.0 Use PSR-16 compatible getMultiple() method instead
     */
    public function fetchMultiple(array $keys): array
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.4.0. Use PSR-16 compatible getMultiple() method instead', __METHOD__), E_USER_DEPRECATED);
        return iterator_to_array($this->getMultiple($keys));
    }

    /**
     * Get multiple items from cache
     *
     * @param iterable<non-empty-string> $keys
     *
     * @throws InvalidKeyException If any of the keys is not valid
     *
     * @return iterable<non-empty-string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * Save multiple cache items
     *
     * @deprecated since 2.4.0 Use PSR-16 compatible setMultiple() method instead
     *
     * @param array<non-empty-string, mixed> $keysAndValues
     */
    public function saveMultiple(iterable $keysAndValues): void
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.4.0. Use PSR-16 compatible setMultiple() method instead', __METHOD__), E_USER_DEPRECATED);
        $this->setMultiple($keysAndValues);
    }

    /**
     * Set multiple cache items
     *
     * @param iterable<non-empty-string, mixed> $values
     *
     * @throws InvalidKeyException If any of the keys is not valid
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }
        return $success;
    }

    /**
     * Delete multiple cache items
     *
     * @param iterable<non-empty-string> $keys
     *
     * @throws InvalidKeyException If any of the keys is not valid
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
    }

    /**
     * Return whether an item is cached
     *
     * @param non-empty-string $key
     *
     * @throws InvalidKeyException If the key is not valid
     */
    abstract public function has(string $key): bool;

    /**
     * Return whether multiple items are cached
     *
     * @param iterable<non-empty-string> $keys
     *
     * @throws InvalidKeyException If any of the keys is not valid
     */
    public function hasMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return the time when an item was cached
     *
     * @param non-empty-string $key
     *
     * @throws InvalidKeyException If the key is not valid
     */
    abstract public function cachedTime(string $key): ?int;

    /**
     * Get a cached item as a `CacheItemInterface` object
     *
     * @param non-empty-string $key
     *
     * @internal
     *
     * @throws InvalidKeyException If the key is not valid
     */
    abstract public function getItem(string $key): ?CacheItemInterface;

    /**
     * Return whether a cache namespace is valid
     */
    protected function isValidNamespace(string $namespace): bool
    {
        return $namespace !== '' && (bool) preg_match(self::NAMESPACE_REGEX, $namespace);
    }

    /**
     * Return whether a cache key is valid
     */
    protected function isValidKey(string $key): bool
    {
        return $key !== '' && strcspn($key, self::RESERVED_KEY_CHARACTERS) === strlen($key);
    }

    /**
     * Parse ttl value and return it as an integer
     */
    protected function parseTtl(int|DateInterval|null $ttl): ?int
    {
        return $ttl instanceof DateInterval
            ? (int) (($now = new DateTimeImmutable())->add($ttl)->getTimestamp() - $now->getTimestamp())
            : $ttl;
    }
}
