<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class FactoryProductFixture
{
    public function __construct(public SharedDependencyFixture $dependency, public string $value) {}
}
