<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class AnotherDependentService
{
    public function __construct(public SharedDependency $dependency) {}
}
