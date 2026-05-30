<?php

namespace Formwork\Interpolator\Nodes;

class ArrayKeysNode extends AbstractNode
{
    public const string TYPE = 'array keys';

    /**
     * @param list<AbstractNode> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
