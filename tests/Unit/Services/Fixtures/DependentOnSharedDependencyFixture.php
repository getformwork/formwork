<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnSharedDependencyFixture
{
    public function __construct(public SharedDependencyFixture $dependency) {}
}
