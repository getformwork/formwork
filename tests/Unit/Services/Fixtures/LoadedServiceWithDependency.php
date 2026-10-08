<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class LoadedServiceWithDependency extends LoadedService
{
    public function __construct(public SharedDependency $dependency)
    {
        parent::__construct('dependency');
    }
}
