<?php

namespace Formwork\Schemes;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Fields\Layout\Layout;
use Formwork\Translations\Translation;
use Formwork\Translations\Translations;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;
use InvalidArgumentException;

class Scheme implements Arrayable
{
    use DataArrayable;

    /**
     * Scheme path
     */
    protected string $path;

    protected string $title;

    protected SchemeOptions $options;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected string $id, array $data, protected Translations $translations, protected Schemes $schemes, protected FieldFactory $fieldFactory)
    {
        $this->data = $data;

        if (isset($this->data['extend'])) {
            $this->extend($this->schemes->get($this->data['extend']));
        }

        $this->options = new SchemeOptions($this->data['options'] ?? []);
    }

    public function options(): SchemeOptions
    {
        return $this->options;
    }

    /**
     * Get scheme id
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get scheme title
     */
    public function title(): string
    {
        if (isset($this->title)) {
            return $this->title;
        }

        $title = $this->data['title'] ?? $this->id;

        if (isset($this->data['title'])) {
            try {
                $title = $this->translate($title, $this->translations->getCurrent());
            } catch (InvalidArgumentException) {
            }
        }

        return $this->title = $title;
    }

    /**
     * Get scheme fields
     */
    public function fields(): FieldCollection
    {
        $fieldCollection = new FieldCollection();

        $fieldCollection->setMultiple(Arr::map($this->data['fields'] ?? [], fn ($data, $name) => $this->fieldFactory->make($name, $data, $fieldCollection)));

        $layout = new Layout($this->data['layout'] ?? ['type' => 'default', 'sections' => []]);

        $fieldCollection->setLayout($layout);

        return $fieldCollection;
    }

    protected function extend(Scheme $scheme): void
    {
        if ($scheme->id === $this->id) {
            throw new InvalidArgumentException(sprintf('Scheme "%s" cannot be extended by itself', $this->id));
        }

        $this->data = array_replace_recursive($scheme->data, $this->data);
    }

    protected function translate(mixed $value, Translation $translation): mixed
    {
        $language = $translation->code();

        if (is_array($value)) {
            if (isset($value[$language])) {
                $value = $value[$language];
            }
        } elseif (!is_string($value)) {
            return $value;
        }

        $interpolate = fn ($value) => is_string($value) ? Str::interpolate($value, fn ($key) => $translation->translate($key)) : $value;

        if (is_array($value)) {
            return Arr::map($value, $interpolate);
        }

        return $interpolate($value);
    }
}
