<?php

namespace Formwork\Schemes;

use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

class Schemes extends Collection
{
    /**
     * Scheme objects storage
     */
    protected array $storage = [];

    /**
     * Load a scheme
     */
    public function load(string $type, string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yml') {
            $name = FileSystem::name($path);
            $this->items[$type][$name] = $path;
            unset($this->storage[$type][$name]);
        }
    }

    /**
     * Load scheme files from a path
     */
    public function loadFromPath(string $type, string $path): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->load($type, FileSystem::joinPaths($path, $file));
        }
    }

    /**
     * Return whether a scheme matching the given type and name is available
     */
    public function has(string $type, string $name): bool
    {
        return isset($this->items[$type][$name]);
    }

    /**
     * Get a scheme from type and name
     */
    public function get(string $type, string $name): Scheme
    {
        if (!$this->has($type, $name)) {
            throw new InvalidArgumentException('Invalid scheme "' . $name . '" of type "' . $type . '"');
        }

        if (isset($this->storage[$type][$name])) {
            return $this->storage[$type][$name];
        }

        return $this->storage[$type][$name] = new Scheme($type, $this->items[$type][$name]);
    }

    /**
     * Create a collection from the given path
     */
    public static function fromPath(string $type, string $path): self
    {
        $instance = new static();
        $instance->loadFromPath($type, $path);
        return $instance;
    }
}
