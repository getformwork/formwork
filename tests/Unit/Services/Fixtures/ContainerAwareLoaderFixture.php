<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class ContainerAwareLoaderFixture implements ServiceLoaderInterface
{
    public function __construct(private Container $container) {}

    public function load(Container $container): LoadedServiceFixture
    {
        return new LoadedServiceFixture($this->container === $container ? 'container-aware' : 'wrong');
    }
}
