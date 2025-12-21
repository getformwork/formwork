<?php

namespace Formwork\Translations;

use Formwork\Config\Config;
use Formwork\Parsers\Yaml;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

class Translations
{
    /**
     * Translation objects storage
     *
     * @var array<string, Translation>
     */
    protected array $storage = [];

    /**
     * @var array<string, list<string>>
     */
    protected array $data = [];

    /**
     * Current translation
     */
    protected Translation $current;

    public function __construct(
        protected Config $config,
    ) {}

    /**
     * Load a translation file
     */
    public function load(string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yaml') {
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
     *
     * @throws InvalidArgumentException If the translation code is invalid and fallback is not requested
     */
    public function get(string $code, bool $fallbackIfInvalid = false): Translation
    {
        if (!$this->has($code)) {
            if ($fallbackIfInvalid) {
                return $this->getFallback();
            }
            throw new InvalidArgumentException(sprintf('Invalid translation code "%s"', $code));
        }

        if (isset($this->storage[$code])) {
            return $this->storage[$code];
        }

        $data = [];

        foreach ($this->data[$code] as $file) {
            /**
             * @var array<string, list<string>|string>
             */
            $data = [...$data, ...Yaml::parseFile($file)];
        }

        $translation = new Translation($code, $data);

        if (($this->config->get('system.translations.fallback')) !== $code) {
            $translation->setFallback($this->getFallback());
        }

        return $this->storage[$code] = $translation;
    }

    /**
     * Get multiple translations from an array of codes
     *
     * @param list<string> $codes
     *
     * @throws InvalidArgumentException If any of the translation codes is invalid and fallback is not requested
     *
     * @return array<string, Translation>
     */
    public function getMultiple(array $codes, bool $fallbackIfInvalid = false): array
    {
        $translations = [];
        foreach ($codes as $code) {
            $translations[$code] = $this->get($code, $fallbackIfInvalid);
        }
        return $translations;
    }

    /**
     * Get all loaded translations
     *
     * @return array<string, Translation>
     */
    public function getAll(): array
    {
        return $this->getMultiple(array_keys($this->data));
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
     *
     * @throws InvalidArgumentException If the fallback translation code is invalid
     */
    public function getCurrent(): Translation
    {
        return $this->current ?? $this->getFallback();
    }

    /**
     * Get the fallback translation
     *
     * @throws InvalidArgumentException If the fallback translation code is invalid
     */
    public function getFallback(): Translation
    {
        $fallbackCode = $this->config->get('system.translations.fallback');
        return $this->get($fallbackCode);
    }
}
