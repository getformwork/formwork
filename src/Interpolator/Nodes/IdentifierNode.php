<?php

namespace Formwork\Interpolator\Nodes;

class IdentifierNode extends AbstractNode
{
    public const string TYPE = 'identifier';

    public function __construct(
        string $value,
        protected ?ArgumentsNode $argumentsNode,
        protected ?AbstractNode $node,
    ) {
        $this->value = $value;
    }

    /**
     * Return node arguments
     */
    public function arguments(): ?ArgumentsNode
    {
        return $this->argumentsNode;
    }

    /**
     * Return the node used to traverse
     */
    public function traverse(): ?AbstractNode
    {
        return $this->node;
    }
}
