<?php

namespace Formwork\Fields;

use Closure;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Translations\Translations;
use Formwork\Traits\Methods;
use Formwork\Utils\Constraint;
use Formwork\Utils\Str;
use Stringable;
use UnexpectedValueException;

class Field implements Arrayable, Stringable
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
    use Translations;

    /**
     * Keys that should not be translated
     *
     * @var list<string>
     */
    protected const array UNTRANSLATABLE_KEYS = ['name', 'type', 'value', 'default', 'translate'];

    /**
     * Field validation status
     */
    protected bool $validated = false;

    /**
     * Whether the field is being validated
     */
    protected bool $validating = false;

    /**
     * @param string               $name                  Field name
     * @param array<string, mixed> $data
     * @param ?FieldCollection     $parentFieldCollection Parent field collection
     *
     * @throws UnexpectedValueException If fields are nested inside other fields
     */
    public function __construct(
        protected string $name,
        array $data = [],
        protected ?FieldCollection $parentFieldCollection = null,
    ) {
        $this->setMultiple($data);

        if ($this->has('fields')) {
            throw new UnexpectedValueException('Fields may not have other fields inside');
        }
    }

    public function __toString(): string
    {
        if ($this->hasMethod('toString')) {
            return (string) $this->callMethod('toString');
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
        return $this->parentFieldCollection;
    }

    /**
     * Return field name with correct syntax to be used in forms
     */
    public function formName(): string
    {
        return $this->get('formName', Str::dotNotationToBrackets($this->name()));
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
    public function value(): mixed
    {
        return $this->get('value', $this->defaultValue());
    }

    /**
     * Get field default value
     */
    public function defaultValue(): mixed
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
        return !$this->isVisible();
    }

    /**
     * Return whether the field is readonly
     */
    public function isReadonly(): bool
    {
        return $this->is('readonly');
    }

    /**
     * Validate field value
     *
     * @throws RecursionException  If there is recursion in field validation
     * @throws ValidationException If the field is required but empty
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
        return $this->get($key, $default) === true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->baseGet($key, $default);

        if ($value instanceof DynamicFieldValue) {
            if ($key === 'value' && !$value->isComputed()) {
                $this->validated = false;
            }

            $value = $value->value();
        }

        if ($this->isTranslatable($key)) {
            return $this->translate($value);
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        if (Str::endsWith($key, '@')) {
            $key = Str::beforeLast($key, '@');
            $value = new DynamicFieldValue($key, $value, $this);
        }

        if ($key === 'value') {
            if ($this->hasMethod('setValue')) {
                $value = $this->callMethod('setValue', [$value]);
            }
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
     *
     * @param array<string, Closure> $methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
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
     * @param list<mixed> $arguments
     */
    protected function callMethod(string $method, array $arguments = []): mixed
    {
        return $this->methods[$method](...[$this, ...$arguments]);
    }
}
