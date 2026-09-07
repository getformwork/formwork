<?php

namespace Formwork\Interpolator\Nodes;

class ArrayNode extends AbstractNode
{
    public const string TYPE = 'array';

    /**
     * @param list<mixed> $value
     */
    public function __construct(
        array $value,
        protected ArrayKeysNode $arrayKeysNode,
    ) {
        $this->value = $value;
    }

    /**
     * Get the array keys node
     */
    public function keys(): ArrayKeysNode
    {
        return $this->arrayKeysNode;
    }
}
