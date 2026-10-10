<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class FactoryProduct
{
    public function __construct(public SharedDependency $dependency, public string $value) {}
}
