<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class NestedDependentService
{
    public function __construct(public DependentOnSharedDependency $dependency) {}
}
