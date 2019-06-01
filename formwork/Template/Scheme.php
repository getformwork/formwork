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
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->path = Formwork::instance()->option('templates.path') . 'schemes' . DS;
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
     *
     * @return string
     */
    public function title()
    {
        return $this->get('title');
    }

    /**
     * Return whether scheme is default
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->get('default', false);
    }
}
