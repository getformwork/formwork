<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnContractFixture
{
    public function __construct(public DependencyContractFixture $dependency) {}
}
