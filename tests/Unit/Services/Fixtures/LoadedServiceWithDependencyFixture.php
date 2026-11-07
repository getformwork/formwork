<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class LoadedServiceWithDependencyFixture extends LoadedServiceFixture
{
    public function __construct(public SharedDependencyFixture $dependency)
    {
        parent::__construct('dependency');
    }
}
