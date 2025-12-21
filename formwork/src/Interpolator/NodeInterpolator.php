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
    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(
        protected AbstractNode $node,
        protected array $vars,
    ) {}

    /**
     * Return the value interpolated from the node
     */
    public function interpolate(): mixed
    {
        if ($this->node instanceof IdentifierNode) {
            return $this->interpolateIdentifierNode($this->node);
        }
        throw new InterpolationException(sprintf('Unexpected %s', $this->node));
    }

    /**
     * Interpolate a node
     */
    protected function interpolateNode(AbstractNode $node): mixed
    {
        if ($node instanceof IdentifierNode) {
            return $this->interpolateIdentifierNode($node);
        }

        if ($node instanceof ArrayNode) {
            return $this->interpolateArrayNode($node);
        }

        return $node->value();
    }

    /**
     * Interpolate an identifier node
     *
     * @param array<mixed>|object|null $parent
     */
    protected function interpolateIdentifierNode(IdentifierNode $identifierNode, array|object|null $parent = null): mixed
    {
        $name = $identifierNode->value();

        $arguments = [];

        $traverse = $identifierNode->traverse();

        if ($identifierNode->arguments() !== null) {
            foreach ($identifierNode->arguments()->value() as $argument) {
                $arguments[] = $this->interpolateNode($argument);
            }
        }

        if ($parent === null) {
            if (!array_key_exists($name, $this->vars)) {
                throw new InterpolationException(sprintf('Undefined variable "%s"', $name));
            }
            $value = $this->vars[$name];
        } elseif (is_array($parent)) {
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

                case property_exists($parent, $name) && $identifierNode->arguments() === null:
                    $value = $parent->{$name};
                    break;

                case is_callable([$parent, '__get']) && $identifierNode->arguments() === null:
                    $value = $parent->__get($name);
                    break;

                case defined($parent::class . '::' . $name) && $identifierNode->arguments() === null:
                    $value = $parent::{$name};
                    break;

                default:
                    throw new InterpolationException(sprintf('Undefined class method, property or constant %s::%s', $parent::class, $name));
            }
        } else {
            throw new InvalidArgumentException(sprintf('%s() accepts only arrays and objects as $parent argument', __METHOD__));
        }

        if ($traverse !== null) {
            if (is_scalar($value)) {
                throw new InterpolationException(sprintf('Scalar value "%s" cannot be traversed like arrays or objects', $value));
            }

            if (is_resource($value)) {
                throw new InterpolationException('Resources cannot be traversed like arrays or objects');
            }

            if ($traverse instanceof NumberNode || $traverse instanceof StringNode) {
                $key = $this->validateArrayKey($traverse->value());

                if (!is_array($value) || !array_key_exists($key, $value)) {
                    throw new InterpolationException(sprintf('Undefined array key "%s"', $key));
                }

                $value = $value[$key];
            } elseif ($traverse instanceof IdentifierNode) {
                $value = $this->interpolateIdentifierNode($traverse, $value);
            } else {
                throw new InterpolationException(sprintf(
                    'Invalid %s',
                    is_object($value) ? 'class method, property or constant name' : 'array key'
                ));
            }
        }

        // Call closures if arguments (zero or more) are given
        if ($value instanceof Closure && $identifierNode->arguments() !== null) {
            return $value(...$arguments);
        }

        return $value;
    }

    /**
     * Interpolate an array node
     *
     * @return array<mixed>
     */
    protected function interpolateArrayNode(ArrayNode $arrayNode): array
    {
        $result = [];
        $keys = $this->interpolateArrayKeysNode($arrayNode->keys());

        foreach ($arrayNode->value() as $i => $value) {
            $key = $keys[$i];
            $result[$key] = $this->interpolateNode($value);
        }

        return $result;
    }

    /**
     * Interpolate an array keys node
     *
     * @return list<array-key>
     */
    protected function interpolateArrayKeysNode(ArrayKeysNode $arrayKeysNode): array
    {
        $offset = -1;

        $result = [];

        foreach ($arrayKeysNode->value() as $key) {
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
    protected function validateArrayKey(mixed $key): int|string
    {
        switch (true) {
            case is_bool($key):
            case is_float($key):
            case is_string($key) && ctype_digit($key) && $key[0] !== '0':
                return (int) $key;

            case is_int($key):
            case is_string($key):
                return $key;

            case $key === null:
                return '';

            default:
                throw new InterpolationException('Invalid non-scalar array key');
        }
    }
}
