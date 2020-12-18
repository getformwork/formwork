<?php

namespace Formwork\Schemes;

use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class Scheme extends DataGetter
{
    /**
     * Scheme path
     *
     * @var string
     */
    protected $path;

    /**
     * Scheme name
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new Scheme instance
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->name = FileSystem::name($path);

        parent::__construct(YAML::parseFile($this->path));

        if ($this->has('extend') && $this->get('extend') !== $this->name) {
            $parent = new static(dirname($this->path) . DS . $this->get('extend') . '.yml');
            $this->data = Arr::appendMissing($this->data, $parent->toArray());
        }

        if (!$this->has('title')) {
            $this->data['title'] = $this->name;
        }
    }

    /**
     * Get scheme title
     */
    public function title(): string
    {
        return $this->get('title');
    }

    /**
     * Return whether scheme is default
     */
    public function isDefault(): bool
    {
        return $this->get('default', false);
    }

    /**
     * Return default field values
     */
    public function defaultFieldValues(): array
    {
        $result = [];
        foreach ($this->get('fields', []) as $name => $value) {
            if (isset($value['default'])) {
                $result[$name] = $value['default'];
            }
        }
        return $result;
    }
}
