<?php

namespace Formwork\Fields;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Formwork;
use Formwork\Traits\Methods;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use UnexpectedValueException;

class Field implements Arrayable
{
    use DataArrayable;
    use DataMultipleGetter {
        get as protected baseGet;
    }
    use DataMultipleSetter;
    use Methods;

    protected const UNTRANSLATABLE_KEYS = ['name', 'type', 'value', 'default', 'translate', 'import'];

    /**
     * Field name
     */
    protected string $name;

    /**
     * Create a new Field instance
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;

        if ($this->has('import')) {
            $this->importData();
        }

        if ($this->has('fields')) {
            throw new UnexpectedValueException('Fields may not have other fields inside');
        }

        $this->loadMethods();
    }

    /**
     * Get field name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return field name with correct syntax to be used in forms
     */
    public function formName(): string
    {
        return Str::dotNotationToBrackets($this->name());
    }

    /**
     * Get field type
     */
    public function type(): string
    {
        return $this->baseGet('type');
    }

    /**
     * Get field label
     */
    public function label(): string
    {
        return $this->get('label', $this->name());
    }

    /**
     * Get field placeholder label
     */
    public function placeholder(): ?string
    {
        return $this->get('placeholder');
    }

    /**
     * Get field value
     */
    public function value()
    {
        return $this->baseGet('value', $this->defaultValue());
    }

    /**
     * Get field default value
     */
    public function defaultValue()
    {
        return $this->baseGet('default');
    }

    /**
     * Return whether field is empty
     */
    public function isEmpty(): bool
    {
        return Constraint::isEmpty($this->value());
    }

    /**
     * Return whether field is required
     */
    public function isRequired(): bool
    {
        return $this->is('required');
    }

    /**
     * Return whether field is disabled
     */
    public function isDisabled(): bool
    {
        return $this->is('disabled');
    }

    /**
     * Return whether the field is visible
     */
    public function isVisible(): bool
    {
        return $this->is('visible', true);
    }

    /**
     * Return whether the field is not visible
     */
    public function isHidden(): bool
    {
        return $this->is('visible', false);
    }

    /**
     * Get a value by key and return whether it is equal to boolean `true`
     */
    public function is(string $key, bool $default = false): bool
    {
        return $this->baseGet($key, $default) === true;
    }

    /**
     * Get field data
     */
    public function get(string $key, $default = null)
    {
        $value = $this->baseGet($key, $default);

        if ($this->isTranslatable($key)) {
            return $this->translate($value);
        }

        return $value;
    }

    /**
     * Load field methods
     */
    protected function loadMethods(): void
    {
        $config = Formwork::instance()->config()->get('fields.path') . $this->get('type') . '.php';

        if (FileSystem::exists($config)) {
            $this->methods = include $config;
        }
    }

    /**
     * Import data helper
     */
    protected function importData(): void
    {
        foreach ((array) $this->data['import'] as $key => $value) {
            if ($key === 'import') {
                throw new UnexpectedValueException('Invalid key for import');
            }

            $callback = explode('::', $value, 2);

            if (!is_callable($callback)) {
                throw new UnexpectedValueException(sprintf('Invalid import callback "%s"', $value));
            }

            $this->data[$key] = $callback();
        }
    }

    /**
     * Return whether a field key is translatable
     */
    protected function isTranslatable(string $key): bool
    {
        if (in_array($key, self::UNTRANSLATABLE_KEYS, true)) {
            return false;
        }

        $translatable = $this->baseGet('translate', true);

        if (is_array($translatable)) {
            return in_array($key, $translatable, true);
        }

        return $translatable;
    }

    /**
     * Translate field value
     */
    protected function translate($value)
    {
        $translation = Formwork::instance()->translations()->getCurrent();
        $language = $translation->code();

        if (is_array($value)) {
            if (isset($value[$language])) {
                $value = $value[$language];
            }
        } elseif (!is_string($value)) {
            return $value;
        }

        $interpolate = fn ($value) => Str::interpolate($value, fn ($key) => $translation->translate($key));

        if (is_array($value)) {
            return Arr::map($value, $interpolate);
        }

        return $interpolate($value);
    }

    /**
     * @inheritdoc
     */
    protected function callMethod(string $method, array $arguments = [])
    {
        return $this->methods[$method](...[$this, ...$arguments]);
    }

    public function __toString(): string
    {
        if ($this->hasMethod('toString')) {
            return $this->callMethod('toString');
        }

        return (string) $this->value();
    }
}
