<?php

namespace Formwork\Cache;

use DateInterval;
use Generator;
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
        // Only cache files are deleted, as the path may contain other files
        foreach ($this->listCacheFiles() as $file) {
            FileSystem::delete(FileSystem::joinPaths($this->path, $file));
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
        return count(iterator_to_array($this->listCacheFiles()));
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

    /**
     * List the names of the files in the cache path which are cache files
     *
     * @return Generator<int, string>
     */
    protected function listCacheFiles(): Generator
    {
        if (!FileSystem::isDirectory($this->path, assertExists: false)) {
            return;
        }
        foreach (FileSystem::listFiles($this->path, includeHidden: true) as $file) {
            if (preg_match('/^[0-9a-f]{64}$/', $file) === 1) {
                yield $file;
            }
        }
    }

    protected function getFile(string $key): string
    {
        return FileSystem::joinPaths($this->path, hash('sha256', $key));
    }
}
