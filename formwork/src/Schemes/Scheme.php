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

/**
 * @property array{title?: array<string, string>|string, extend?: string, options?: array<string, mixed>, layout?: array<string, mixed>, fields?: array<string, mixed>} $data
 */
class Scheme implements Arrayable
{
    use DataArrayable;

    /**
     * Scheme path
     */
    protected string $path;

    /**
     * Scheme title
     */
    protected string $title;

    /**
     * Scheme options
     */
    protected SchemeOptions $options;

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException If the extended scheme ID is invalid
     * @throws InvalidArgumentException If a scheme tries to extend itself
     */
    public function __construct(
        protected string $id,
        array $data,
        protected Translations $translations,
        protected Schemes $schemes,
        protected FieldFactory $fieldFactory,
    ) {
        $this->data = $data;

        if (isset($this->data['extend'])) {
            $this->extend($this->schemes->get($this->data['extend']));
        }

        $this->options = new SchemeOptions($this->data['options'] ?? []);
    }

    /**
     * Get scheme options
     */
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

        // @phpstan-ignore argument.templateType
        $fieldCollection->setMultiple(Arr::map($this->data['fields'] ?? [], fn($data, $name) => $this->fieldFactory->make($name, $data, $fieldCollection)));

        $layout = new Layout($this->data['layout'] ?? ['sections' => ['default' => ['fields' => array_keys($this->data['fields'] ?? [])]]], $this->translations->getCurrent());

        $fieldCollection->setLayout($layout);

        return $fieldCollection;
    }

    /**
     * Extend the scheme with another scheme
     *
     * @throws InvalidArgumentException If the scheme tries to extend itself
     */
    public function extend(Scheme $scheme): void
    {
        if ($scheme->id === $this->id) {
            throw new InvalidArgumentException(sprintf('Scheme "%s" cannot be extended by itself', $this->id));
        }

        $this->data = Arr::extend($scheme->data, $this->data);
    }

    /**
     * Extend the scheme with an array of data
     *
     * @param array<string, mixed> $data
     */
    public function extendWith(array $data): void
    {
        $this->data = Arr::extend($data, $this->data);
    }

    /**
     * Get the extended scheme, if any
     */
    public function getExtendedScheme(): ?Scheme
    {
        return isset($this->data['extend']) ? $this->schemes->get($this->data['extend']) : null;
    }

    /**
     * Translate a value
     */
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

        $interpolate = fn($value) => is_string($value) ? Str::interpolate($value, fn($key) => $translation->translate($key)) : $value;

        if (is_array($value)) {
            return Arr::map($value, $interpolate);
        }

        return $interpolate($value);
    }
}
