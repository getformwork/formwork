<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnMissingServiceFixture
{
    public function __construct(UnknownDependency $dependency) {}
}
