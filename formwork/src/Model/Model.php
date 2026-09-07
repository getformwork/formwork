<?php

namespace Formwork\Model;

use BadMethodCallException;
use Formwork\Cms\App;
use Formwork\Data\Attributes\Getter;
use Formwork\Data\Attributes\Setter;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Fields\Field;
use Formwork\Fields\FieldCollection;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Arr;
use LogicException;
use ReflectionAttribute;
use ReflectionProperty;

/**
 * @template TData of array<string, mixed> = array<string, mixed>
 */
class Model implements Arrayable
{
    /**
     * @use DataMultipleGetter<TData>
     */
    use DataMultipleGetter;

    /**
     * @use DataMultipleSetter<TData>
     */
    use DataMultipleSetter;

    /**
     * Identifier used to reference the model
     */
    protected const string MODEL_IDENTIFIER = 'model';

    /**
     * Model data
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Application instance
     */
    protected App $app;

    /**
     * Model scheme
     */
    protected Scheme $scheme;

    /**
     * Model fields
     */
    protected FieldCollection $fields;

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        throw new LogicException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    /**
     * Return the model identifier
     */
    public function getModelIdentifier(): string
    {
        return static::MODEL_IDENTIFIER;
    }

    /**
     * Return the model scheme
     */
    #[Getter]
    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    /**
     * Return the model fields
     */
    #[Getter]
    public function fields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Return whether a key is present
     */
    public function has(string $key): bool
    {
        if (isset($this->dataGetters()[$key])) {
            return true;
        }
        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            trigger_error(sprintf('Checking the existence of the %s::$%s property implicitly with the has() method is deprecated since Formwork 2.4.0. Add the %s attribute to the property to explicitly allow this behavior', static::class, $key, Getter::class), E_USER_DEPRECATED);
            return true;
        }
        if ($this->fields->has($key)) {
            return true;
        }
        return Arr::has($this->data, $key);
    }

    /**
     * Get data by key returning a default value if key is not present
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($getter = $this->dataGetters()[$key] ?? null) {
            return match ($getter['type']) {
                'property' => $this->{$getter['name']},
                'method'   => $this->{$getter['name']}(),
            };
        }

        // Get values from property
        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            // Call getter method if exists. We check property existence before
            // to avoid using get to call methods arbitrarily
            if (method_exists($this, $key)) {
                trigger_error(sprintf('Using the implicit getter method %s::%s() is deprecated since Formwork 2.4.0. Add the %s attribute to the method to make it explicit', static::class, $key, Getter::class), E_USER_DEPRECATED);
                return $this->{$key}();
            }

            trigger_error(sprintf('Getting the %s::$%s property implicitly with the get() method is deprecated since Formwork 2.4.0. Add the %s attribute to the property to explicitly allow this behavior', static::class, $key, Getter::class), E_USER_DEPRECATED);
            return $this->{$key} ?? $default;
        }

        // Get values from fields
        if ($this->fields->has($key)) {
            /** @var Field */
            $field = $this->fields->get($key);

            // If defined use the value returned by `return()`
            if ($field->hasMethod('return')) {
                return $field->return();
            }

            return $field->value();
        }

        // Get values from data
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Set a data value by key
     *
     * This method updates both the data array and the corresponding field
     * (if it exists). The field's validation may transform the value before
     * it's stored in the data array.
     */
    public function set(string $key, mixed $value): void
    {
        if ($setter = $this->dataSetters()[$key] ?? null) {
            match ($setter['type']) {
                'property' => $this->{$setter['name']} = $value,
                'method'   => $this->{$setter['name']}($value),
            };
            return;
        }

        if (isset($this->dataGetters()[$key])) {
            throw new LogicException(sprintf('Cannot set getter-only key %s', $key));
        }

        if (property_exists($this, $key) && !(new ReflectionProperty($this, $key))->isPromoted()) {
            if ($this->isReadonly($key)) {
                throw new BadMethodCallException(sprintf('Cannot set readonly model property %s::$%s', static::class, $key));
            }

            // If defined use a setter
            if (method_exists($this, $setter = 'set' . ucfirst($key))) {
                trigger_error(sprintf('Using the implicit setter method %s::set%s() is deprecated since Formwork 2.4.0. Add the %s attribute to the method to make it explicit', static::class, ucfirst($key), Setter::class), E_USER_DEPRECATED);
                $this->{$setter}($value);
                return;
            }

            trigger_error(sprintf('Setting the %s::$%s property implicitly with the set() method is deprecated since Formwork 2.4.0. Add the %s attribute to the property %s::$%s to explicitly allow this behavior', static::class, $key, Setter::class, static::class, $key), E_USER_DEPRECATED);
            $this->{$key} = $value;
            return;
        }

        // Set value in the corresponding field if exists
        // Note: This updates the field in $this->fields, which may not be
        // the same instance as a cloned field collection used elsewhere
        if (isset($this->fields) && $this->fields->has($key)) {
            /** @var Field */
            $field = $this->fields->get($key);
            $field->set('value', $value);
            $field->validate();

            // Update value according to field validation
            $value = $field->value();
        }

        Arr::set($this->data, $key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->dataGetters() as $key => $accessor) {
            if ($accessor['export']) {
                $data[$key] = match ($accessor['type']) {
                    'method'   => $this->{$accessor['name']}(),
                    'property' => $this->{$accessor['name']},
                };
            }
        }

        $properties = array_diff(
            array_keys(get_class_vars(static::class)),
            array_keys($this->dataGetters()),
            ['data', 'dataAccessors']
        );

        if (count($properties) > 0) {
            trigger_error(sprintf('Getting the following properties implicitly with the toArray() method is deprecated since Formwork 2.4.0: %s. Add the %s(export: true) attribute to the properties to explicitly allow this behavior', implode(', ', array_map(fn($property) => static::class . '::$' . $property, $properties)), Getter::class), E_USER_DEPRECATED);
        }

        /** @var list<string> $properties */
        $data += [...$this->data, ...$this->getMultiple($properties)];

        ksort($data);

        return $data;
    }

    /**
     * Return the model data
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get the application instance
     *
     * @since 2.3.0
     */
    protected function app(): App
    {
        return $this->app ?? App::instance();
    }

    /**
     * Return whether a property has the `ReadonlyModelProperty` attribute
     */
    private function isReadonly(string $property): bool
    {
        $attributes = (new ReflectionProperty($this, $property))->getAttributes(ReadonlyModelProperty::class, ReflectionAttribute::IS_INSTANCEOF);
        return $attributes !== [];
    }
}
