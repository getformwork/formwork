<?php

namespace Formwork\Cache;

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
    protected $time;

    /**
     * Create a new FilesCache instance
     */
    public function __construct(string $path, int $time)
    {
        $this->path = $path;
        $this->time = $time;
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
            $data = FileSystem::read($this->getFile($key));
            return unserialize($data);
        }
        if (!$this->isValid($key)) {
            FileSystem::delete($this->getFile($key));
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(string $key, $value)
    {
        $data = serialize($value);
        FileSystem::write($this->getFile($key), $data);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $key)
    {
        if ($this->has($key)) {
            FileSystem::delete($this->getFile($key));
        }
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        FileSystem::delete($this->path, true);
        FileSystem::createDirectory($this->path, true);
    }

    /**
     * @inheritdoc
     */
    public function has(string $key)
    {
        return FileSystem::exists($this->getFile($key)) && $this->isValid($key);
    }

    /**
     * Return the file that corresponds to the given key
     *
     * @return string
     */
    protected function getFile(string $key)
    {
        return $this->path . sha1($key);
    }

    /**
     * Return whether a cached resource has not expired
     *
     * @return bool
     */
    protected function isValid(string $key)
    {
        $lastModified = FileSystem::lastModifiedTime($this->getFile($key));
        $expires = $lastModified + $this->time;
        return time() < $expires;
    }

    public function __debugInfo()
    {
        return [
            'path' => $this->path,
            'time' => $this->time
        ];
    }
}
