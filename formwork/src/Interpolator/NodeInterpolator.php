<?php

namespace Formwork\Interpolator;

use Closure;
use Formwork\Interpolator\Exceptions\InterpolationException;
use Formwork\Interpolator\Nodes\AbstractNode;
use Formwork\Interpolator\Nodes\ArrayKeysNode;
use Formwork\Interpolator\Nodes\ArrayNode;
use Formwork\Interpolator\Nodes\IdentifierNode;
use Formwork\Interpolator\Nodes\ImplicitArrayKeyNode;
use Formwork\Interpolator\Nodes\NumberNode;
use Formwork\Interpolator\Nodes\StringNode;
use InvalidArgumentException;

class NodeInterpolator
{
    protected AbstractNode $node;

    protected array $vars;

    public function __construct(AbstractNode $node, array $vars)
    {
        $this->node = $node;
        $this->vars = $vars;
    }

    /**
     * Return the value interpolated from the node
     */
    public function interpolate()
    {
        switch ($this->node->type()) {
            case IdentifierNode::TYPE:
                return $this->interpolateIdentifierNode($this->node);

            default:
                throw new InterpolationException('Unexpected ' . $this->node);
        }
    }

    /**
     * Interpolate a node
     */
    protected function interpolateNode(AbstractNode $node)
    {
        switch ($node->type()) {
            case IdentifierNode::TYPE:
                return $this->interpolateIdentifierNode($node);

            case ArrayNode::TYPE:
                return $this->interpolateArrayNode($node);

            default:
                return $node->value();
        }
    }

    /**
     * Interpolate an identifier node
     */
    protected function interpolateIdentifierNode(IdentifierNode $node, array|object|null $parent = null)
    {
        $name = $node->value();

        $arguments = [];

        $traverse = $node->traverse();

        if ($node->arguments() !== null) {
            foreach ($node->arguments()->value() as $argument) {
                $arguments[] = $this->interpolateNode($argument);
            }
        }

        if ($parent === null) {
            if (!array_key_exists($name, $this->vars)) {
                throw new InterpolationException(sprintf('Undefined variable "%s"', $name));
            }
            $value = $this->vars[$name];
        } else {
            if (is_array($parent)) {
                if (!array_key_exists($name, $parent)) {
                    throw new InterpolationException(sprintf('Undefined array key "%s"', $name));
                }
                $value = $parent[$name];
            } elseif (is_object($parent)) {
                switch (true) {
                    case method_exists($parent, $name):
                        $value = $parent->{$name}(...$arguments);
                        break;

                    case is_callable([$parent, '__call']):
                        $value = $parent->__call($name, $arguments);
                        break;

                    case property_exists($parent, $name) && $node->arguments() === null:
                        $value = $parent->{$name};
                        break;

                    case is_callable([$parent, '__get']) && $node->arguments() === null:
                        $value = $parent->__get($name);
                        break;

                    case defined($parent::class . '::' . $name) && $node->arguments() === null:
                        $value = constant($parent::class . '::' . $name);
                        break;

                    default:
                        throw new InterpolationException(sprintf('Undefined class method, property or constant %s::%s', $parent::class, $name));
                }
            } else {
                throw new InvalidArgumentException(sprintf('%s() accepts only arrays and objects as $parent argument', __METHOD__));
            }
        }

        if ($traverse !== null) {
            if (is_scalar($value)) {
                throw new InterpolationException(sprintf('Scalar value "%s" cannot be traversed like arrays or objects', $value));
            }

            if (is_resource($value)) {
                throw new InterpolationException(sprintf('%s cannot be traversed like arrays or objects', $value));
            }

            switch ($traverse->type()) {
                case NumberNode::TYPE:
                case StringNode::TYPE:
                    $key = $this->validateArrayKey($traverse->value());

                    if (!is_array($value) || !array_key_exists($key, $value)) {
                        throw new InterpolationException(sprintf('Undefined array key "%s"', $key));
                    }

                    $value = $value[$key];
                    break;

                case IdentifierNode::TYPE:
                    $value = $this->interpolateIdentifierNode($traverse, $value);
                    break;

                default:
                    throw new InterpolationException(sprintf(
                        'Invalid %s of type "%s"',
                        is_object($value) ? 'class method, property or constant name' : 'array key'
                    ));
            }
        }

        // Call closures if arguments (zero or more) are given
        if ($value instanceof Closure && $node->arguments() !== null) {
            return $value(...$arguments);
        }

        return $value;
    }

    /**
     * Interpolate an array node
     */
    protected function interpolateArrayNode(ArrayNode $node): array
    {
        $result = [];
        $keys = $this->interpolateArrayKeysNode($node->keys());

        foreach ($node->value() as $i => $value) {
            $key = $keys[$i];
            $result[$key] = $this->interpolateNode($value);
        }

        return $result;
    }

    /**
     * Interpolate an array keys node
     */
    protected function interpolateArrayKeysNode(ArrayKeysNode $node): array
    {
        $offset = -1;

        $result = [];

        foreach ($node->value() as $key) {
            switch ($key->type()) {
                case ImplicitArrayKeyNode::TYPE:
                    $offset++;
                    $result[] = $offset;
                    continue 2; // break out of the switch and continue the foreach loop

                case NumberNode::TYPE:
                case StringNode::TYPE:
                    $value = $key->value();
                    break;

                case IdentifierNode::TYPE:
                    $value = $this->interpolateIdentifierNode($key);
                    break;

                default:
                    throw new InterpolationException(sprintf('Invalid array key type "%s"', $key->type()));
            }

            $value = $this->validateArrayKey($value);

            if (is_int($value)) {
                $offset = $value;
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * Validate array key
     *
     * @see https://www.php.net/manual/en/language.types.array.php
     */
    protected function validateArrayKey($key): int|string
    {
        switch (true) {
            case is_int($key):
                return $key;
                break;

            case is_bool($key):
            case is_float($key):
            case is_string($key) && ctype_digit($key) && $key[0] !== '0':
                return (int) $key;

            case is_string($key):
                return $key;
                break;

            case $key === null:
                return '';
                break;

            default:
                throw new InterpolationException('Invalid non-scalar array key');
        }
    }
}
