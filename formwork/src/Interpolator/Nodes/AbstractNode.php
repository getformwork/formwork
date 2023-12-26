<?php

namespace Formwork\Interpolator\Nodes;

abstract class AbstractNode
{
    /**
     * Node type
     */
    public const TYPE = 'node';

    /**
     * Node value
     */
    protected mixed $value;

    public function __toString()
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
