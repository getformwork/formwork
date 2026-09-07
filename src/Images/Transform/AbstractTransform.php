<?php

namespace Formwork\Images\Transform;

use Formwork\Parsers\Php;
use Formwork\Utils\Str;
use ReflectionClass;

abstract class AbstractTransform implements TransformInterface
{
    public function toArray(): array
    {
        $data = [];

        foreach ((new ReflectionClass($this))->getProperties() as $reflectionProperty) {
            $data[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
        }

        return $data;
    }

    public function getSpecifier(): string
    {
        $arguments = [];

        foreach ($this->toArray() as $key => $value) {
            $arguments[] = $key . ': ' . Php::encode($value);
        }

        return Str::afterLast(static::class, '\\') . '(' . implode(', ', $arguments) . ')';
    }
}
