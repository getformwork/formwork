<?php

namespace Formwork\Schemes;

use Exception;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Fields\Layout\Layout;
use Formwork\Translations\Translations;
use Formwork\Utils\Arr;

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

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $id, array $data, protected Translations $translations, protected Schemes $schemes, protected FieldFactory $fieldFactory)
    {
        $this->id = $id;
        $this->data = $data;

        if (isset($this->data['extend'])) {
            $this->extend($this->schemes->get($this->data['extend']));
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
        $collection = new FieldCollection();

        $collection->setMultiple(Arr::map($this->data['fields'] ?? [], fn ($data, $name) => $this->fieldFactory->make($name, $data, $collection)));

        $layout = new Layout($this->data['layout'] ?? ['type' => 'default', 'sections' => []]);

        $collection->setLayout($layout);

        return $collection;
    }

    protected function extend(Scheme $scheme): void
    {
        if ($scheme->id === $this->id) {
            throw new Exception(sprintf('Scheme "%s" cannot be extended by itself', $this->id));
        }

        $this->data = array_replace_recursive($scheme->data, $this->data);
    }
}
