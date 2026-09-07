<?php

namespace Formwork\Interpolator\Nodes;

use Stringable;

abstract class AbstractNode implements Stringable
{
    /**
     * Node type
     */
    public const string TYPE = 'node';

    /**
     * Node value
     */
    protected mixed $value;

    public function __toString(): string
    {
        return 'node of type ' . static::TYPE;
    }

    /**
     * Get node type
     */
    public function type(): string
    {
        return static::TYPE;
    }

    /**
     * Get node value
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
