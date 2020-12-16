<?php

namespace Formwork\Template;

use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\Arr;

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
    public function __construct(string $name)
    {
        $this->path = Formwork::instance()->config()->get('templates.path') . 'schemes' . DS;
        $this->name = $name;

        parent::__construct(YAML::parseFile($this->path . $this->name . '.yml'));

        if ($this->has('extend') && $this->get('extend') !== $this->name) {
            $parent = new static($this->get('extend'));
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
