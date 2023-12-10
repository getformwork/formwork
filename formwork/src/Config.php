<?php

namespace Formwork;

use Exception;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;
use Formwork\Parsers\Yaml;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class Config implements Arrayable
{
    use DataArrayable;
    use DataGetter {
        get as baseGet;
    }

    protected const INTERPOLATION_REGEX = '/\$(?!\$)\{([%a-z._]+)\}/i';

    protected bool $resolved;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->resolved = false;
    }

    public function get(string $key, $default = null)
    {
        if (!$this->resolved) {
            throw new Exception('Unresolved config');
        }
        return $this->baseGet($key, $default);
    }

    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listFiles($path) as $file) {
            $this->loadFile(FileSystem::joinPaths($path, $file));
        }
    }

    public function loadFile(string $path): void
    {
        if (FileSystem::isReadable($path) && FileSystem::extension($path) === 'yaml') {
            $name = FileSystem::name($path);

            $data = (array) Yaml::parseFile($path);

            if (isset($this->data[$name])) {
                $this->data[$name] = array_replace_recursive($this->data[$name], $data);
            } else {
                $this->data[$name] = $data;
            }

        }
    }

    public function resolve(array $vars = []): void
    {
        array_walk_recursive($this->data, function (&$value) use ($vars): void {
            if (is_string($value)) {
                $value = preg_replace_callback(self::INTERPOLATION_REGEX, function ($matches) use ($vars) {
                    $key = $matches[1];

                    if (!Arr::has($this->data, $key) && !Arr::has($vars, $key)) {
                        throw new Exception();
                    }

                    $value = Arr::get($this->data, $key, Arr::get($vars, $key));

                    if (!is_string($value)) {
                        throw new Exception();
                    }

                    return $value;
                }, $value);
            }
        });

        $this->resolved = true;
    }
}
