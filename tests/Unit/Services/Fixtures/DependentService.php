<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentService
{
    public function __construct(public SharedDependency $dependency, public string $label) {}
}
