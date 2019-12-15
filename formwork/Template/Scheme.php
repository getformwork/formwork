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
    public function __construct(string $name)
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

        if ($this->has('pages')) {
            if ($this->data['pages'] === false) {
                $this->data['children'] = false;
            }
            if (is_array($this->data['pages'])) {
                $this->data['children']['templates'] = $this->data['pages'];
            }
            unset($this->data['pages']);
            trigger_error('Property "pages" in page schemes is deprecated since Formwork 1.2.0, use "children" or "children.templates" instead', E_USER_DEPRECATED);
        }

        if ($this->has('reverse-children')) {
            $this->data['children']['reverse'] = $this->data['reverse-children'];
            unset($this->data['reverse-children']);
            trigger_error('Property "reverse-children" in page schemes is deprecated since Formwork 1.2.0, use "children.reverse" instead', E_USER_DEPRECATED);
        }

        if ($this->has('sortable-children')) {
            $this->data['children']['sortable'] = $this->data['sortable-children'];
            unset($this->data['sortable-children']);
            trigger_error('Property "sortable-children" in page schemes is deprecated since Formwork 1.2.0, use "children.sortable" instead', E_USER_DEPRECATED);
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

    /**
     * Return default field values
     *
     * @return array
     */
    public function defaultFieldValues()
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
