<?php

namespace Formwork\Interpolator\Nodes;

class NumberNode extends AbstractNode
{
    /**
     * @inheritdoc
     */
    public const TYPE = 'number';

    public function __construct(float|int $value)
    {
        $this->value = $value;
    }
}
