<?php

namespace Formwork\Schemes;

use Formwork\Parsers\Yaml;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use InvalidArgumentException;

class Schemes
{
    /**
     * Scheme objects storage
     *
     * @var array<string, Scheme>
     */
    protected array $storage = [];

    /**
     * @var array<string, string>
     */
    protected array $data = [];

    public function __construct(
        protected SchemeFactory $schemeFactory,
    ) {}

    /**
     * Load a scheme
     */
    public function load(string $id, string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yaml') {
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
            $id = str_replace(DIRECTORY_SEPARATOR, '.', Str::beforeLast($item, '.'));
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
     *
     * @throws InvalidArgumentException If the scheme ID is invalid or not found
     */
    public function get(string $id): Scheme
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(sprintf('Invalid scheme "%s"', $id));
        }

        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }

        $data = Yaml::parseFile($this->data[$id]);

        return $this->storage[$id] = $this->schemeFactory->make($id, $data);
    }
}
