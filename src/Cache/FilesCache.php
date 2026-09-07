<?php

namespace Formwork\Cache;

use DateInterval;
use Formwork\Cache\Exceptions\InvalidKeyException;
use Formwork\Cache\Exceptions\InvalidNamespaceException;
use Formwork\Parsers\Php;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

class FilesCache extends AbstractCache implements CountableCache
{
    /**
     * Cache path
     */
    protected string $path;

    /**
     * @param string                $path       Cache path
     * @param non-empty-string      $namespace  Cache namespace
     * @param DateInterval|int|null $defaultTtl Cached data time-to-live
     *
     * @throws InvalidNamespaceException If the namespace is not valid
     */
    public function __construct(
        string $path,
        protected string $namespace,
        protected int|DateInterval|null $defaultTtl = null,
    ) {
        if (!$this->isValidNamespace($this->namespace)) {
            throw new InvalidNamespaceException(sprintf('Namespace "%s" is not valid', $this->namespace));
        }
        $this->path = FileSystem::joinPaths($path, $this->namespace);
    }

    /**
     * Return the cache path
     */
    public function path(): string
    {
        return $this->path;
    }

    public function namespace(): string
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

        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, recursive: true);
        }

        Php::encodeToFile(
            new CacheItem($value, $expirationTime, $cachedTime),
            $this->getFile($key)
        );

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

        if (!($cacheItem = Php::parseFile($file)) instanceof CacheItem) {
            throw new UnexpectedValueException(sprintf('Cache file "%s" does not contain a valid cache item', $file));
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
