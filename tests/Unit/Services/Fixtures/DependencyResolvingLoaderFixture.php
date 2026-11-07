<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class DependencyResolvingLoaderFixture implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedServiceFixture
    {
        return new LoadedServiceWithDependencyFixture($container->get(SharedDependencyFixture::class));
    }
}
