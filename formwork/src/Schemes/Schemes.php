<?php

namespace Formwork\Schemes;

use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use InvalidArgumentException;

class Schemes
{
    /**
     * Scheme objects storage
     */
    protected array $storage = [];

    protected array $data = [];

    /**
     * Load a scheme
     */
    public function load(string $id, string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yml') {
            $this->data[$id] = $path;
            unset($this->storage[$id]);
        }
    }

    /**
     * Load scheme files from a path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listRecursive($path) as $item) {
            $id = str_replace(DS, '.', Str::beforeLast($item, '.'));
            $this->load($id, FileSystem::joinPaths($path, $item));
        }
    }

    /**
     * Return whether a scheme matching the given id is available
     */
    public function has(string $id): bool
    {
        return isset($this->data[$id]);
    }

    /**
     * Get a scheme from id
     */
    public function get(string $id): Scheme
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(sprintf('Invalid scheme "%s"', $id));
        }

        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }

        return $this->storage[$id] = new Scheme($id, YAML::parseFile($this->data[$id]));
    }

    /**
     * Create a collection from the given path
     */
    public static function fromPath(string $path): self
    {
        $instance = new static();
        $instance->loadFromPath($path);
        return $instance;
    }
}
