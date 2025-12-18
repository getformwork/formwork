<?php

namespace Formwork\Config;

use Formwork\Config\Exceptions\ConfigLoadingException;
use Formwork\Config\Exceptions\ConfigResolutionException;
use Formwork\Config\Exceptions\UnresolvedConfigException;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Parsers\Yaml;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class Config implements ArraySerializable
{
    /**
     * Regex pattern for config interpolation
     */
    protected const string INTERPOLATION_REGEX = '/\$(?!\$)\{([%a-z._]+)\}/i';

    /**
     * @param array<string, mixed> $config
     */
    final public function __construct(
        protected array $config = [],
        protected bool $resolved = false,
    ) {}

    /**
     * Check if a key exists in the config
     */
    public function has(string $key): bool
    {
        return Arr::has($this->config, $key);
    }

    /**
     * Get a value from the config
     *
     * @throws UnresolvedConfigException If the config has not been resolved
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->resolved) {
            throw new UnresolvedConfigException('Unresolved config');
        }
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Load config from a path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->loadFile(FileSystem::joinPaths($path, $file));
        }
    }

    /**
     * Load config from a file
     *
     * @throws ConfigLoadingException If the config file does not exist or has an unsupported type
     */
    public function loadFile(string $path): void
    {
        if (!FileSystem::isFile($path)) {
            throw new ConfigLoadingException(sprintf('Config file "%s" does not exist', $path));
        }

        $name = FileSystem::name($path);
        $extension = FileSystem::extension($path);

        switch ($extension) {
            case 'php':
                $data = (array) include $path;
                break;

            case 'yaml':
                $data = (array) Yaml::parseFile($path);
                break;

            default:
                throw new ConfigLoadingException(sprintf('Unsupported config file type "%s"', $extension));
        }

        $this->config[$name] = isset($this->config[$name]) ? array_replace_recursive($this->config[$name], $data) : $data;
    }

    /**
     * Resolve config values with the given variables
     *
     * @param array<string, string> $vars
     *
     * @throws ConfigResolutionException If a config value cannot be resolved with undefined key or non-string value
     */
    public function resolve(array $vars = []): void
    {
        $resolver = function (&$array) use ($vars) {
            array_walk_recursive($array, function (&$value) use ($vars, &$array): void {
                if (is_string($value)) {
                    $value = preg_replace_callback(self::INTERPOLATION_REGEX, function ($matches) use ($vars, &$array) {
                        $key = $matches[1];

                        if (!Arr::has($array, $key) && !Arr::has($vars, $key)) {
                            throw new ConfigResolutionException(sprintf('Cannot resolve a config value with undefined key or variable "%s"', $key));
                        }

                        $value = Arr::get($array, $key, Arr::get($vars, $key));

                        if (!is_string($value)) {
                            throw new ConfigResolutionException(sprintf('Cannot resolve a config value with non-string "%s"', $key));
                        }

                        return $value;
                    }, $value);
                }
            });
        };

        $resolver($this->config);

        $this->resolved = true;
    }

    /**
     * Get config as array
     *
     * @throws UnresolvedConfigException If the config has not been resolved
     */
    public function toArray(): array
    {
        if (!$this->resolved) {
            throw new UnresolvedConfigException('Unresolved config');
        }
        return $this->config;
    }

    public static function fromArray(array $data): static
    {
        $static = new static($data['config']);
        $static->resolved = true;
        return $static;
    }
}
