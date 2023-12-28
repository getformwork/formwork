<?php

namespace Formwork\Cache;

use Formwork\Parsers\Php;
use Formwork\Utils\FileSystem;

class FilesCache extends AbstractCache
{
    /**
     * @param string $path       Cache path
     * @param int    $defaultTtl Cached data time-to-live
     */
    public function __construct(protected string $path, protected int $defaultTtl)
    {
        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, recursive: true);
        }
    }

    /**
     * @inheritdoc
     */
    public function fetch(string $key): mixed
    {
        if ($this->has($key)) {
            $cacheItem = Php::parseFile($this->getFile($key));
            return $cacheItem->value();
        }
        if ($this->hasExpired($key)) {
            FileSystem::delete($this->getFile($key));
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(string $key, mixed $value, ?int $ttl = null): void
    {
        $cacheItem = new CacheItem($value, time() + ($ttl ?? $this->defaultTtl), time());
        Php::encodeToFile($cacheItem, $this->getFile($key));
    }

    /**
     * @inheritdoc
     */
    public function delete(string $key): void
    {
        if ($this->has($key)) {
            FileSystem::delete($this->getFile($key));
        }
    }

    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        FileSystem::delete($this->path, recursive: true);
        FileSystem::createDirectory($this->path, recursive: true);
    }

    /**
     * @inheritdoc
     */
    public function has(string $key): bool
    {
        return FileSystem::exists($this->getFile($key)) && !$this->hasExpired($key);
    }

    /**
     * @inheritdoc
     */
    public function cachedTime(string $key): ?int
    {
        return $this->has($key) ? FileSystem::lastModifiedTime($this->getFile($key)) : null;
    }

    /**
     * Return the file that corresponds to the given key
     */
    protected function getFile(string $key): string
    {
        return FileSystem::joinPaths($this->path, hash('sha256', $key));
    }

    /**
     * Return whether a cached resource has not expired
     */
    protected function hasExpired(string $key): bool
    {
        $cacheItem = Php::parseFile($this->getFile($key));
        return time() >= $cacheItem->expirationTime();
    }
}
