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
     *
     * @param string $path
     * @param int    $time
     */
    public function __construct($path, $time)
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
    public function fetch($key)
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
    public function save($key, $value)
    {
        $data = serialize($value);
        FileSystem::write($this->getFile($key), $data);
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
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
    public function has($key)
    {
        return FileSystem::exists($this->getFile($key)) && $this->isValid($key);
    }

    /**
     * Return the file that corresponds to the given key
     *
     * @param string $key
     *
     * @return string
     */
    protected function getFile($key)
    {
        return $this->path . sha1($key);
    }

    /**
     * Return whether a cached resource has not expired
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isValid($key)
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
