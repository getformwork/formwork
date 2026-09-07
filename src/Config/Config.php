<?php

namespace Formwork\Config;

use Formwork\Config\Exceptions\ConfigLoadingException;
use Formwork\Config\Exceptions\ConfigResolutionException;
use Formwork\Config\Exceptions\UnresolvedConfigException;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Parsers\Yaml;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use UnexpectedValueException;

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
     * Check if multiple keys exist in the config
     *
     * @param list<string> $keys
     *
     * @since 2.3.0
     */
    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
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
     * Get a string value from the config
     *
     * @throws UnexpectedValueException If the config value is not a string
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);
        if (!is_string($value)) {
            throw new UnexpectedValueException(sprintf('Config value for key "%s" is not a string, got %s', $key, get_debug_type($value)));
        }
        return $value;
    }

    /**
     * Get a boolean value from the config
     *
     * @throws UnexpectedValueException If the config value is not a boolean
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);
        if (!is_bool($value)) {
            throw new UnexpectedValueException(sprintf('Config value for key "%s" is not a boolean, got %s', $key, get_debug_type($value)));
        }
        return $value;
    }

    /**
     * Get an integer value from the config
     *
     * @throws UnexpectedValueException If the config value is not an integer
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);
        if (!is_int($value)) {
            throw new UnexpectedValueException(sprintf('Config value for key "%s" is not an integer, got %s', $key, get_debug_type($value)));
        }
        return $value;
    }

    /**
     * Get a float value from the config
     *
     * @throws UnexpectedValueException If the config value is not a float
     */
    public function getFloat(string $key, ?float $default = null): float
    {
        $value = $this->get($key, $default);
        if (!is_float($value)) {
            throw new UnexpectedValueException(sprintf('Config value for key "%s" is not a float, got %s', $key, get_debug_type($value)));
        }
        return $value;
    }

    /**
     * Get an array value from the config
     *
     * @param ?array<mixed> $default
     *
     * @throws UnexpectedValueException If the config value is not an array
     *
     * @return array<mixed>
     */
    public function getArray(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);
        if (!is_array($value)) {
            throw new UnexpectedValueException(sprintf('Config value for key "%s" is not an array, got %s', $key, get_debug_type($value)));
        }
        return $value;
    }

    /**
     * Get multiple values from the config
     *
     * @param list<string> $keys
     *
     * @throws UnresolvedConfigException If the config has not been resolved
     *
     * @return array<string, mixed>
     *
     * @since 2.3.0
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * Set a value in the config
     *
     * @since 2.3.0
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($this->config, $key, $value);
    }

    /**
     * Set multiple values in the config
     *
     * @param array<string, mixed> $values
     *
     * @since 2.3.0
     */
    public function setMultiple(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Load config from a path
     */
    public function loadFromPath(string $path, ?string $prefix = null): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->loadFile(FileSystem::joinPaths($path, $file), $prefix);
        }
    }

    /**
     * Load config from a file
     *
     * @throws ConfigLoadingException If the config file does not exist or has an unsupported type
     */
    public function loadFile(string $path, ?string $prefix = null): void
    {
        if (!FileSystem::isFile($path)) {
            throw new ConfigLoadingException(sprintf('Config file "%s" does not exist', $path));
        }

        $name = Str::toCamelCase(FileSystem::name($path));
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

        $key = $prefix !== null ? "{$prefix}.{$name}" : $name;
        $this->config = Arr::extend($this->config, Arr::undot([$key => $data]));
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
