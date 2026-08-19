<?php

namespace Formwork\Cache;

use DateInterval;
use Formwork\Cache\Exceptions\InvalidKeyException;
use Formwork\Cache\Exceptions\InvalidNamespaceException;

class ArrayCache extends AbstractCache implements CountableCache
{
    /**
     * @var array<string, CacheItem>
     */
    protected array $items = [];

    /**
     * @param ?string               $namespace  Cache namespace
     * @param DateInterval|int|null $defaultTtl Cached data time-to-live
     *
     * @throws InvalidNamespaceException If the namespace is not valid
     */
    public function __construct(
        protected ?string $namespace = null,
        protected int|DateInterval|null $defaultTtl = null,
    ) {
        if (!$this->isValidNamespace($this->namespace)) {
            throw new InvalidNamespaceException(sprintf('Namespace "%s" is not valid', $this->namespace));
        }
    }

    public function namespace(): ?string
    {
        return $this->namespace;
    }

    public function defaultTtl(): int|DateInterval|null
    {
        return $this->defaultTtl;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return ($cacheItem = $this->getItem($key)) !== null
            ? $cacheItem->value()
            : $default;
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        if (!$this->isValidKey($key)) {
            throw new InvalidKeyException(sprintf('Key "%s" is not valid', $key));
        }

        $cachedTime = time();
        $expirationTime = ($ttl = $this->parseTtl($ttl ?? $this->defaultTtl)) !== null
            ? $cachedTime + $ttl
            : null;

        $this->items[$key] = new CacheItem($value, $expirationTime, $cachedTime);

        return true;
    }

    public function delete(string $key): bool
    {
        if (!$this->isValidKey($key)) {
            throw new InvalidKeyException(sprintf('Key "%s" is not valid', $key));
        }
        unset($this->items[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->items = [];
        return true;
    }

    public function has(string $key): bool
    {
        return $this->getItem($key) !== null;
    }

    public function cachedTime(string $key): ?int
    {
        return $this->getItem($key)?->cachedTime();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @internal
     */
    public function getItem(string $key): ?CacheItem
    {
        if (!$this->isValidKey($key)) {
            throw new InvalidKeyException(sprintf('Key "%s" is not valid', $key));
        }

        if (!isset($this->items[$key])) {
            return null;
        }

        $cacheItem = $this->items[$key];

        if ($cacheItem->isExpired()) {
            unset($this->items[$key]);
            return null;
        }

        return $cacheItem;
    }
}
