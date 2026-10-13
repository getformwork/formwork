<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnSharedDependency
{
    public function __construct(public SharedDependency $dependency) {}
}
