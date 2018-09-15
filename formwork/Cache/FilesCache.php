<?php

namespace Formwork\Cache;

use Formwork\Utils\FileSystem;

class FilesCache extends AbstractCache
{
    protected $path;

    protected $time;

    public function __construct($path, $time)
    {
        $this->path = $path;
        $this->time = $time;
        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, true);
        }
    }

    public function fetch($key)
    {
        if ($this->has($key) && $this->isValid($key)) {
            $data = FileSystem::read($this->getFile($key));
            return unserialize($data);
        }
        return null;
    }

    public function save($key, $value)
    {
        $data = serialize($value);
        FileSystem::write($this->getFile($key), $data);
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            FileSystem::delete($this->getFile($key));
        }
    }

    public function has($key)
    {
        return FileSystem::exists($this->getFile($key));
    }

    protected function getFile($key)
    {
        return $this->path . sha1($key);
    }

    protected function isValid($key)
    {
        $lastModified = FileSystem::lastModifiedTime($this->getFile($key));
        $expires = $lastModified + $this->time;
        return time() < $expires;
    }

    public function __debugInfo()
    {
        return array(
            'path' => $this->path,
            'time' => $this->time
        );
    }
}
