<?php

namespace Formwork\Schemes;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Exceptions\RecursionException;
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
     * @var array{
     *     title?: array<string, string>|string,
     *     extend?: string,
     *     options?: array<string, mixed>,
     *     layout?: array<string, mixed>,
     *     fields?: array<string, mixed>
     * }
     */
    protected array $data = [];

    /**
     * Scheme IDs currently being extended.
     *
     * @var array<string, true>
     */
    protected static array $extending = [];

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
     * @param array{
     *     title?: array<string, string>|string,
     *     extend?: string,
     *     options?: array<string, mixed>,
     *     layout?: array<string, mixed>,
     *     fields?: array<string, mixed>
     * } $data
     *
     * @throws InvalidArgumentException If the extended scheme ID is invalid
     * @throws InvalidArgumentException If a scheme tries to extend itself
     * @throws RecursionException       If there is recursion in scheme extension
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
            $this->extend($this->data['extend']);
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

        $fieldCollection->setMultiple(Arr::map(
            $this->data['fields'] ?? [],
            fn($data, $name) => $this->fieldFactory->make($name, $data, $fieldCollection)
        ));

        $layout = new Layout($this->data['layout'] ?? ['sections' => [
            'default' => [
                'label'  => $this->data['title'] ?? 'default',
                'fields' => array_keys($this->data['fields'] ?? []),
            ],
        ]], $this->translations->getCurrent());

        $fieldCollection->setLayout($layout);

        return $fieldCollection;
    }

    /**
     * Extend the scheme with another scheme
     *
     * @param Scheme|string $scheme Scheme instance or scheme id to extend with
     *
     * @throws InvalidArgumentException If the scheme tries to extend itself
     * @throws InvalidArgumentException If the scheme ID is invalid or not found
     * @throws RecursionException       If there is recursion in scheme extension
     */
    public function extend(Scheme|string $scheme): void
    {
        $id = $scheme instanceof Scheme ? $scheme->id : $scheme;

        if ($id === $this->id) {
            throw new InvalidArgumentException(sprintf('Scheme "%s" cannot be extended by itself', $this->id));
        }

        if (isset(self::$extending[$this->id])) {
            throw new RecursionException(sprintf('Recursion in the extension of the scheme "%s". Extension chain: "%s"', $this->id, implode('" > "', [...array_keys(self::$extending), $this->id])));
        }

        self::$extending[$this->id] = true;

        try {
            $base = $scheme instanceof Scheme ? $scheme : $this->schemes->get($id);
            $this->extendWith($base->data);
        } finally {
            unset(self::$extending[$this->id]);
        }
    }

    /**
     * Extend the scheme with an array of data
     *
     * @param array{
     *     title?: array<string, string>|string,
     *     extend?: string,
     *     options?: array<string, mixed>,
     *     layout?: array<string, mixed>,
     *     fields?: array<string, mixed>
     * } $data
     *
     * @since 2.3.0
     */
    public function extendWith(array $data): void
    {
        // @phpstan-ignore assign.propertyType
        $this->data = Arr::extend($data, $this->data);
    }

    /**
     * Get the extended scheme, if any
     *
     * @since 2.3.0
     */
    public function getExtendedScheme(): ?Scheme
    {
        return isset($this->data['extend']) ? $this->schemes->get($this->data['extend']) : null;
    }

    /**
     * Check if the scheme extends another scheme
     */
    public function extendsScheme(Scheme|string $scheme): bool
    {
        $id = $scheme instanceof Scheme ? $scheme->id() : $scheme;
        while ($extendedScheme = $this->getExtendedScheme()) {
            if ($extendedScheme->id() === $id) {
                return true;
            }
            $extendedScheme = $extendedScheme->getExtendedScheme();
        }
        return false;
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
