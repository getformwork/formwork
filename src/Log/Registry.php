<?php

namespace Formwork\Log;

use Formwork\Parsers\Json;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

class Registry
{
    /**
     * Stored registry entries
     *
     * @var array<string, mixed>
     */
    protected array $storage = [];

    /**
     * Whether the registry is loaded
     */
    protected bool $loaded = false;

    /**
     * Whether the registry is saved
     */
    protected bool $saved = false;

    public function __construct(
        protected string $filename,
    ) {}

    /**
     * Save the registry on instance destruction
     */
    public function __destruct()
    {
        if ($this->loaded && !$this->saved) {
            $this->save();
        }
    }

    /**
     * Return whether a key is in the registry
     */
    public function has(string $key): bool
    {
        $this->load();
        return isset($this->storage[$key]);
    }

    /**
     * Get a key from the registry
     */
    public function get(string $key): mixed
    {
        $this->load();
        if ($this->has($key)) {
            return $this->storage[$key];
        }
        throw new UnexpectedValueException(sprintf('Undefined key "%s"', $key));
    }

    /**
     * Add a key to the registry
     */
    public function set(string $key, mixed $value): void
    {
        $this->load();
        $this->storage[$key] = $value;
        $this->saved = false;
    }

    /**
     * Remove a key from the registry
     */
    public function remove(string $key): void
    {
        $this->load();
        if ($this->has($key)) {
            unset($this->storage[$key]);
            $this->saved = false;
        }
    }

    /**
     * Save the registry to file
     */
    public function save(): void
    {
        $this->load();
        if (!FileSystem::isDirectory($directory = dirname($this->filename), assertExists: false)) {
            FileSystem::createDirectory($directory, true);
        }
        Json::encodeToFile($this->storage, $this->filename);
        $this->saved = true;
    }

    /**
     * Convert the registry to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $this->load();
        return $this->storage;
    }

    /**
     * Load the registry from file
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        if (FileSystem::exists($this->filename)) {
            $this->storage = Json::parseFile($this->filename);
            $this->saved = true;
        }
        $this->loaded = true;
    }
}
