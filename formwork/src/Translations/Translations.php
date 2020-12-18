<?php

namespace Formwork\Translations;

use Formwork\Core\Formwork;
use Formwork\Data\Collection;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

class Translations extends Collection
{
    /**
     * Translation objects storage
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Load a translation file
     */
    public function load(string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yml') {
            $code = FileSystem::name($path);
            $this->items[$code][] = $path;
            unset($this->storage[$code]);
        }
    }

    /**
     * Load translation files from a path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->load(FileSystem::joinPaths($path, $file));
        }
    }

    /**
     * Return whether a translation matching the given code is available
     */
    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    /**
     * Get a translation from code
     */
    public function get(string $code, bool $fallbackIfInvalid = false): Translation
    {
        if (!$this->has($code)) {
            if ($fallbackIfInvalid) {
                return $this->getFallback();
            }
            throw new InvalidArgumentException('Invalid translation "' . $code . '"');
        }

        if (isset($this->storage[$code])) {
            return $this->storage[$code];
        }

        $data = [];

        foreach ($this->items[$code] as $file) {
            $data = array_merge($data, YAML::parseFile($file));
        }

        return $this->storage[$code] = new Translation($code, $data);
    }

    /**
     * Get the fallback translation
     */
    public function getFallback(): Translation
    {
        $fallbackCode = Formwork::instance()->config()->get('translations.fallback');
        return $this->get($fallbackCode);
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
