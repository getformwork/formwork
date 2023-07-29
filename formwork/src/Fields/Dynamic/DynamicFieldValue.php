<?php

namespace Formwork\Fields\Dynamic;

use Formwork\Exceptions\RecursionException;
use Formwork\Fields\Field;
use Formwork\Interpolator\Interpolator;

class DynamicFieldValue
{
    public static array $vars = [];

    /**
     * Dynamic value computation status
     */
    protected bool $computed = false;

    /**
     * Whether the dynamic value is being computed
     */
    protected bool $computing = false;

    /**
     * Dynamic value key
     */
    protected string $key;

    /**
     * Uncomputed value
     */
    protected string $uncomputedValue;

    /**
     * Field to which the value belongs
     */
    protected Field $field;

    /**
     * Computed value
     */
    protected $value;

    public function __construct(string $key, string $uncomputedValue, Field $field)
    {
        $this->key = $key;
        $this->uncomputedValue = $uncomputedValue;
        $this->field = $field;
    }

    /**
     * Create an instance with an already computed value
     * (used by field validation)
     *
     * @internal
     */
    public static function withComputed(string $value, self $dynamic): self
    {
        $instance = clone $dynamic;
        $instance->computed = true;
        $instance->value = $value;
        return $instance;
    }

    /**
     * Compute dynamic field value
     */
    public function compute(): void
    {
        if ($this->computed) {
            return;
        }

        if ($this->computing) {
            throw new RecursionException(sprintf('Recursion in the computation of dynamic property "%s" of field "%s". Trying to compute "%s"', $this->key, $this->field->name(), $this->uncomputedValue));
        }

        $this->computing = true;

        $this->value = Interpolator::interpolate($this->uncomputedValue, [...self::$vars, 'this' => $this->field]);

        $this->computed = true;

        $this->computing = false;
    }

    /**
     * Get the key associated to the dynamic value
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Get the computed value
     */
    public function value()
    {
        if (!$this->computed) {
            $this->compute();
        }

        return $this->value;
    }

    /**
     * Return whether the dynamic value has been computed
     */
    public function isComputed(): bool
    {
        return $this->computed;
    }

    /**
     * Return the field to which the dynamic value belongs
     */
    public function field(): Field
    {
        return $this->field;
    }
}
