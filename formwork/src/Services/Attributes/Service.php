<?php

namespace Formwork\Services\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Service
{
    public function __construct(
        public readonly string $name,
    ) {}
}
