<?php

namespace Formwork\Schemes;

use Exception;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Formwork;

class Scheme implements Arrayable
{
    use DataArrayable;

    /**
     * Scheme path
     */
    protected string $path;

    /**
     * Scheme name
     */
    protected string $id;

    protected SchemeOptions $options;

    public function __construct(string $id, array $data)
    {
        $this->id = $id;
        $this->data = $data;

        if (isset($this->data['extend'])) {
            $this->extend($this->data['extend']);
        }

        $this->data['title'] ??= $this->id;

        $this->options = new SchemeOptions($this->data['options'] ?? []);
    }

    public function options(): SchemeOptions
    {
        return $this->options;
    }

    /**
     * Get scheme title
     */
    public function title(): string
    {
        return $this->data['title'];
    }

    /**
     * Return whether scheme is default
     */
    public function isDefault(): bool
    {
        return $this->data['default'] ?? false;
    }

    /**
     * Get scheme fields
     */
    public function fields(): FieldCollection
    {
        return new FieldCollection(
            $this->data['fields'] ?? [],
            new Layout($this->data['layout'] ?? ['type' => 'default', 'sections' => []])
        );
    }

    protected function extend(string $id): void
    {
        if ($id === $this->id) {
            throw new Exception(sprintf('Scheme "%s" cannot be extended by itself', $this->id));
        }

        $parent = Formwork::instance()->schemes()->get($id);

        $this->data = array_replace_recursive($parent->data, $this->data);
    }
}
