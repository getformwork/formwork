<?php

namespace Formwork\Cache;

use DateInterval;
use Formwork\Cache\Exceptions\InvalidKeyException;
use Formwork\Parsers\Php;
use Formwork\Utils\FileSystem;
use Throwable;
use UnexpectedValueException;

class FilesCache extends AbstractCache implements CountableCache
{
    /**
     * @param string                $path       Cache path
     * @param DateInterval|int|null $defaultTtl Cached data time-to-live
     */
    public function __construct(
        protected string $path,
        protected int|DateInterval|null $defaultTtl = null,
    ) {}

    /**
     * Return the cache path
     */
    public function path(): string
    {
        return $this->path;
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

        $cacheItem = new CacheItem($value, $expirationTime, $cachedTime);

        if ($cacheItem->isExpired()) {
            return $this->delete($key);
        }

        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, recursive: true);
        }

        Php::encodeToFile($cacheItem, $this->getFile($key));

        return true;
    }

    public function delete(string $key): bool
    {
        if (!$this->isValidKey($key)) {
            throw new InvalidKeyException(sprintf('Key "%s" is not valid', $key));
        }

        if (FileSystem::exists($file = $this->getFile($key))) {
            FileSystem::delete($file);
        }

        return true;
    }

    public function clear(): bool
    {
        if (FileSystem::exists($this->path)) {
            FileSystem::delete($this->path, recursive: true);
            FileSystem::createDirectory($this->path, recursive: true);
        }
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
        if (!FileSystem::exists($this->path)) {
            return 0;
        }
        return count(iterator_to_array(FileSystem::listFiles($this->path)));
    }

    /**
     * @internal
     */
    public function getItem(string $key): ?CacheItem
    {
        if (!$this->isValidKey($key)) {
            throw new InvalidKeyException(sprintf('Key "%s" is not valid', $key));
        }

        $file = $this->getFile($key);

        if (!FileSystem::exists($file)) {
            return null;
        }

        try {
            $throwable = null;
            $cacheItem = Php::parseFile($file);
        } catch (Throwable $throwable) {
            $cacheItem = null;
        }

        if (!($cacheItem instanceof CacheItem)) {
            throw new UnexpectedValueException(sprintf('Cache file "%s" does not contain a valid cache item', $file), previous: $throwable);
        }

        if ($cacheItem->isExpired()) {
            FileSystem::delete($file);
            return null;
        }

        return $cacheItem;
    }

    protected function getFile(string $key): string
    {
        return FileSystem::joinPaths($this->path, hash('sha256', $key));
    }
}
