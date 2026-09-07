<?php

namespace Formwork\Interpolator\Nodes;

class NumberNode extends AbstractNode
{
    public const string TYPE = 'number';

    public function __construct(float|int $value)
    {
        $this->value = $value;
    }
}
