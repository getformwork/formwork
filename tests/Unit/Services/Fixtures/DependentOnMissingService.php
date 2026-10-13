<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnMissingService
{
    public function __construct(UnknownDependency $dependency) {}
}
