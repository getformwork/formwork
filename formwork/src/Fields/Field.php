<?php

namespace Formwork\Fields;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Traits\Methods;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\Str;
use UnexpectedValueException;

class Field implements Arrayable
{
    use DataArrayable;
    use DataMultipleGetter {
        get as protected baseGet;
    }
    use DataMultipleSetter {
        set as protected baseSet;
        remove as protected baseRemove;
    }
    use Methods;

    protected const UNTRANSLATABLE_KEYS = ['name', 'type', 'value', 'default', 'translate'];

    /**
     * Field name
     */
    protected string $name;

    /**
     * Parent field collection
     */
    protected ?FieldCollection $parent;

    /**
     * Field validation status
     */
    protected bool $validated = false;

    /**
     * Whether the field is being validated
     */
    protected bool $validating = false;

    protected Translation $translation;

    /**
     * Create a new Field instance
     */
    public function __construct(string $name, array $data = [], ?FieldCollection $parent = null)
    {
        $this->name = $name;

        $this->parent = $parent;

        $this->setMultiple($data);

        if ($this->has('fields')) {
            throw new UnexpectedValueException('Fields may not have other fields inside');
        }
    }

    public function __toString(): string
    {
        if ($this->hasMethod('toString')) {
            return $this->callMethod('toString');
        }

        return (string) $this->value();
    }

    /**
     * Get field name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return the parent field collection
     */
    public function parent(): ?FieldCollection
    {
        return $this->parent;
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
        return $this->get('value', $this->defaultValue());
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
     * Validate field value
     */
    public function validate(): static
    {
        if ($this->validating) {
            throw new RecursionException(sprintf('Recursion in the validation of field "%s" of type "%s"', $this->name(), $this->type()));
        }

        $this->validating = true;

        $value = $this->value();

        $dynamic = $value instanceof DynamicFieldValue ? $value : null;

        if ($dynamic !== null) {
            $value = $value->value();
        }

        if ($this->isRequired() && Constraint::isEmpty($value)) {
            throw new ValidationException(sprintf('Required field "%s" of type "%s" cannot be empty', $this->name(), $this->type()));
        }

        if ($this->hasMethod('validate')) {
            $value = $this->callMethod('validate', [$value]);

            if ($dynamic !== null) {
                $value = DynamicFieldValue::withComputed($value, $dynamic);
            }

            $this->set('value', $value);
        }

        $this->validated = true;

        $this->validating = false;

        return $this;
    }

    /**
     * Return whether the field is valid
     */
    public function isValid(): bool
    {
        try {
            $this->validate();
        } catch (ValidationException) {
            return false;
        }
        return true;
    }

    /**
     * Return whether the field has been validated
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * Get a value by key and return whether it is equal to boolean `true`
     */
    public function is(string $key, bool $default = false): bool
    {
        return $this->baseGet($key, $default) === true;
    }

    public function get(string $key, $default = null)
    {
        $value = $this->baseGet($key, $default);

        if ($value instanceof DynamicFieldValue) {
            if ($key === 'value' && !$value->isComputed()) {
                $this->validated = false;
            }

            $value = $value->value();
        }

        if ($this->isTranslatable($key)) {
            $value = $this->translate($value);
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        if (Str::endsWith($key, '@')) {
            $key = Str::beforeLast($key, '@');
            $value = new DynamicFieldValue($key, $value, $this);
        }

        if ($key === 'value') {
            $this->validated = false;
        }

        $this->baseSet($key, $value);
    }

    public function remove(string $key): void
    {
        if ($key === 'value') {
            $this->validated = false;
        }

        $this->baseRemove($key);
    }

    /**
     * Load field methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }

    public function setTranslation(Translation $translation): void
    {
        $this->translation = $translation;
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
        if (!isset($this->translation)) {
            return $value;
        }

        $language = $this->translation->code();

        if (is_array($value)) {
            if (isset($value[$language])) {
                $value = $value[$language];
            }
        } elseif (!is_string($value)) {
            return $value;
        }

        $interpolate = fn ($value) => is_string($value) ? Str::interpolate($value, fn ($key) => $this->translation->translate($key)) : $value;

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
}
