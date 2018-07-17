<?php

namespace Formwork\Cache;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;

class Cache
{
    protected $path;

    protected $time;

    public function __construct()
    {
        $this->path = Formwork::instance()->option('cache.path');
        $this->time = Formwork::instance()->option('cache.time');
        if (!FileSystem::exists($this->path)) {
            FileSystem::createDirectory($this->path, true);
        }
    }

    public function fetch($key)
    {
        $file = $this->path . $key;
        if ($this->has($key)) {
            $lastModified = FileSystem::lastModifiedTime($file);
            $expires = $lastModified + $this->time;
            if (Formwork::instance()->site()->modifiedSince($lastModified) or time() > $expires) {
                $this->delete($key);
                return null;
            }
            return FileSystem::read($file);
        }
    }

    public function has($key)
    {
        $file = $this->path . $key;
        return FileSystem::exists($file);
    }

    public function save($key, $value)
    {
        $file = $this->path . $key;
        FileSystem::write($file, $value);
    }

    public function delete($key)
    {
        $file = $this->path . $key;
        if ($this->has($key)) {
            FileSystem::exists($file);
        }
    }

    public function __debugInfo()
    {
        return array(
            'path'      => $this->path,
            'time'      => $this->time
        );
    }
}
