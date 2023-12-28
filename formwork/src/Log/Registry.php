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
     * Whether the registry is saved
     */
    protected bool $saved = false;

    /**
     * Create a new Registry instance
     */
    public function __construct(protected string $filename)
    {
        if (FileSystem::exists($this->filename)) {
            $this->storage = Json::parseFile($filename);
            $this->saved = true;
        }
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

    /**
     * Return whether a key is in the registry
     */
    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get a key from the registry
     */
    public function get(string $key): mixed
    {
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
        $this->storage[$key] = $value;
        $this->saved = false;
    }

    /**
     * Remove a key from the registry
     */
    public function remove(string $key): void
    {
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
        return $this->storage;
    }
}
