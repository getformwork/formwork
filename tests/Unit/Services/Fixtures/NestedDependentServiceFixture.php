<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class NestedDependentServiceFixture
{
    public function __construct(public DependentOnSharedDependencyFixture $dependency) {}
}
