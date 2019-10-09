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
    protected $storage = array();

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
     *
     * @param string $filename
     */
    public function __construct($filename)
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
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get a key from the registry
     *
     * @param string $key
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->storage[$key];
        }
    }

    /**
     * Add a key to the registry
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
        $this->saved = false;
    }

    /**
     * Remove a key from the registry
     *
     * @param string $key
     */
    public function remove($key)
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
