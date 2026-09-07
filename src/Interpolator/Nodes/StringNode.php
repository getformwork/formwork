<?php

namespace Formwork\Interpolator\Nodes;

class StringNode extends AbstractNode
{
    public const string TYPE = 'string';

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
