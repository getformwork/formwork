<?php

namespace Formwork\Images\Transform;

use Formwork\Parsers\Php;
use Formwork\Utils\Str;

abstract class AbstractTransform implements TransformInterface
{
    public function toArray(): array
    {
        return get_object_vars($this);
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
