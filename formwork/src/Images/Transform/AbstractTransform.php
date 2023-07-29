<?php

namespace Formwork\Images\Transform;

use Formwork\Data\Contracts\ArraySerializable;
use Formwork\Images\ImageInfo;
use Formwork\Parsers\Php;
use Formwork\Utils\Str;
use GdImage;

abstract class AbstractTransform implements ArraySerializable
{
    abstract public function apply(GdImage $image, ImageInfo $info): GdImage;

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
