<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentServiceFixture
{
    public function __construct(public SharedDependencyFixture $dependency, public string $label) {}
}
