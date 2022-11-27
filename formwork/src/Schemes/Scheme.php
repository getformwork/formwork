<?php

namespace Formwork\Schemes;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Scheme implements Arrayable
{
    use DataArrayable;
    use DataGetter;

    /**
     * Scheme type
     */
    protected string $type;

    /**
     * Scheme path
     */
    protected string $path;

    /**
     * Scheme name
     */
    protected string $name;

    /**
     * Create a new Scheme instance
     */
    public function __construct(string $type, string $path)
    {
        $this->type = $type;
        $this->path = $path;
        $this->name = FileSystem::name($path);

        $this->data = YAML::parseFile($this->path);

        if ($this->has('extend') && $this->get('extend') !== $this->name) {
            $parent = Formwork::instance()->schemes()->get($type, $this->get('extend'));
            $this->data = array_replace_recursive($parent->data, $this->data);
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
     * Get scheme fields
     */
    public function fields(): FieldCollection
    {
        return new FieldCollection(
            $this->get('fields', []),
            new Layout($this->get('layout', ['type' => 'default', 'sections' => []]))
        );
    }
}
