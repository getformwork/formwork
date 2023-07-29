<?php

namespace Formwork\Images\Transform;

use Formwork\Data\AbstractCollection;

class TransformCollection extends AbstractCollection
{
    protected ?string $dataType = AbstractTransform::class;

    protected bool $mutable = true;

    public function getSpecifier(): string
    {
        return implode('|', $this->everyItem()->getSpecifier()->values());
    }
}
