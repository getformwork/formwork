<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class AnotherDependentServiceFixture
{
    public function __construct(public SharedDependencyFixture $dependency) {}
}
