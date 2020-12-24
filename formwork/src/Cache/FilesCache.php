<?php

namespace Formwork\Cache;

use Formwork\Parsers\PHP;
use Formwork\Utils\FileSystem;

class FilesCache extends AbstractCache
{
    /**
     * Cache path
     *
     * @var string
     */
    protected $path;

    /**
     * Cached data time-to-live
     *
     * @var int
     */
    protected $defaultTtl;

    /**
     * Create a new FilesCache instance
     */
    public function __construct(string $path, int $defaultTtl)
    {
        $this->path = $path;
        $this->defaultTtl = $defaultTtl;
        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, true);
        }
    }

    /**
     * @inheritdoc
     */
    public function fetch(string $key)
    {
        if ($this->has($key)) {
            $data = PHP::parseFile($this->getFile($key));
            return $data['value'];
        }
        if ($this->hasExpired($key)) {
            FileSystem::delete($this->getFile($key));
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(string $key, $value, int $ttl = null): void
    {
        $data = ['value' => $value, 'expires' => time() + ($ttl ?? $this->defaultTtl)];
        PHP::encodeToFile($data, $this->getFile($key));
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
        FileSystem::delete($this->path, true);
        FileSystem::createDirectory($this->path, true);
    }

    /**
     * @inheritdoc
     */
    public function has(string $key): bool
    {
        return FileSystem::exists($this->getFile($key)) && !$this->hasExpired($key);
    }

    /**
     * Return the file that corresponds to the given key
     */
    protected function getFile(string $key): string
    {
        return $this->path . hash('sha256', $key);
    }

    /**
     * Return whether a cached resource has not expired
     */
    protected function hasExpired(string $key): bool
    {
        $data = PHP::parseFile($this->getFile($key));
        return time() >= $data['expires'];
    }
}
