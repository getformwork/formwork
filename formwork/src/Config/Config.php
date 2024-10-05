<?php

namespace Formwork\Config;

use Formwork\Config\Exceptions\ConfigResolutionException;
use Formwork\Config\Exceptions\UnresolvedConfigException;
use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Parsers\Yaml;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class Config implements ArraySerializable
{
    protected const INTERPOLATION_REGEX = '/\$(?!\$)\{([%a-z._]+)\}/i';

    protected bool $resolved = false;

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $defaults
     */
    final public function __construct(protected array $config = [], protected array $defaults = [])
    {
    }

    public function has(string $key): bool
    {
        return Arr::has($this->config, $key);
    }

    public function hasDefaults(string $key): bool
    {
        return Arr::has($this->defaults, $key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->resolved) {
            throw new UnresolvedConfigException('Unresolved config');
        }
        return Arr::get($this->config, $key, $default);
    }

    public function getDefaults(string $key, mixed $default = null): mixed
    {
        if (!$this->resolved) {
            throw new UnresolvedConfigException('Unresolved config');
        }
        return Arr::get($this->defaults, $key, $default);
    }

    public function loadFromPath(string $path, bool $defaultConfig = false): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->loadFile(FileSystem::joinPaths($path, $file), $defaultConfig);
        }
    }

    public function loadFile(string $path, bool $defaultConfig = false): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yaml') {
            $name = FileSystem::name($path);
            $data = (array) Yaml::parseFile($path);
            if ($defaultConfig) {
                $this->defaults[$name] = isset($this->defaults[$name]) ? array_replace_recursive($this->defaults[$name], $data) : $data;
            }
            $this->config[$name] = isset($this->config[$name]) ? array_replace_recursive($this->config[$name], $data) : $data;
        }
    }

    /**
     * @param array<string, string> $vars
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

        $resolver($this->defaults);
        $resolver($this->config);

        $this->resolved = true;
    }

    public function toArray(): array
    {
        if (!$this->resolved) {
            throw new UnresolvedConfigException('Unresolved config');
        }
        return [
            'config'   => $this->config,
            'defaults' => $this->defaults,
        ];
    }

    public static function fromArray(array $data): static
    {
        $static = new static($data['config'], $data['defaults']);
        $static->resolved = true;
        return $static;
    }
}
