<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\FileSystem;

class Registry
{
    protected $storage = array();

    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
        if (FileSystem::exists($this->filename)) {
            $this->storage = (array) json_decode(FileSystem::read($filename), true);
        }
    }

    public function has($key)
    {
        return isset($this->storage[$key]);
    }

    public function get($key)
    {
        if ($this->has($key)) {
            return $this->storage[$key];
        }
    }

    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->storage[$key]);
        }
    }

    public function save()
    {
        FileSystem::write($this->filename, json_encode($this->storage));
    }

    public function toArray()
    {
        return $this->storage;
    }

    public function __destruct()
    {
        $this->save();
    }
}
