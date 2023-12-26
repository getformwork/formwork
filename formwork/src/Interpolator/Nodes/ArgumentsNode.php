<?php

namespace Formwork\Interpolator\Nodes;

class ArgumentsNode extends AbstractNode
{
    /**
     * @inheritdoc
     */
    public const TYPE = 'arguments';

    /**
     * @param list<AbstractNode> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
