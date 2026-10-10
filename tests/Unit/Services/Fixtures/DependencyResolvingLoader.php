<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class DependencyResolvingLoader implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedService
    {
        return new LoadedServiceWithDependency($container->get(SharedDependency::class));
    }
}
