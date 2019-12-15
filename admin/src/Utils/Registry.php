<?php

namespace Formwork\Admin\Utils;

use Formwork\Utils\FileSystem;

class Registry
{
    /**
     * Stored registry entries
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Registry filename
     *
     * @var string
     */
    protected $filename;

    /**
     * Whether the registry is saved
     *
     * @var bool
     */
    protected $saved = false;

    /**
     * Create a new Registry instance
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
        if (FileSystem::exists($this->filename)) {
            $this->storage = (array) json_decode(FileSystem::read($filename), true);
            $this->saved = true;
        }
    }

    /**
     * Return whether a key is in the registry
     *
     * @return bool
     */
    public function has(string $key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get a key from the registry
     */
    public function get(string $key)
    {
        if ($this->has($key)) {
            return $this->storage[$key];
        }
    }

    /**
     * Add a key to the registry
     */
    public function set(string $key, $value)
    {
        $this->storage[$key] = $value;
        $this->saved = false;
    }

    /**
     * Remove a key from the registry
     */
    public function remove(string $key)
    {
        if ($this->has($key)) {
            unset($this->storage[$key]);
            $this->saved = false;
        }
    }

    /**
     * Save the registry to file
     */
    public function save()
    {
        FileSystem::write($this->filename, json_encode($this->storage));
        $this->saved = true;
    }

    /**
     * Convert the registry to array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->storage;
    }

    /**
     * Save the registry on instance destruction
     */
    public function __destruct()
    {
        if (!$this->saved) {
            $this->save();
        }
    }
}
