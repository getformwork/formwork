<?php

namespace Formwork\Translations;

use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

class Translations
{
    /**
     * Translation objects storage
     */
    protected array $storage = [];

    /**
     * Current translation
     */
    protected Translation $current;

    /**
     * Load a translation file
     */
    public function load(string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yml') {
            $code = FileSystem::name($path);
            $this->data[$code][] = $path;
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
        return isset($this->data[$code]);
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

        foreach ($this->data[$code] as $file) {
            $data = array_merge($data, YAML::parseFile($file));
        }

        return $this->storage[$code] = new Translation($code, $data);
    }

    /**
     * Set current translation from language code
     */
    public function setCurrent(string $code): void
    {
        $this->current = $this->get($code, true);
    }

    /**
     * Get current translation
     */
    public function getCurrent(): Translation
    {
        return $this->current ?? $this->getFallback();
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
