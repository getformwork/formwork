<?php

namespace Formwork\Interpolator\Nodes;

class ArrayNode extends AbstractNode
{
    /**
     * @inheritdoc
     */
    public const TYPE = 'array';

    protected ArrayKeysNode $keys;

    /**
     * @param list<mixed> $value
     */
    public function __construct(array $value, ArrayKeysNode $keys)
    {
        $this->value = $value;
        $this->keys = $keys;
    }

    /**
     * Get the array keys node
     */
    public function keys(): ArrayKeysNode
    {
        return $this->keys;
    }
}
