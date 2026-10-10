<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class ContainerAwareLoader implements ServiceLoaderInterface
{
    public function __construct(private Container $container) {}

    public function load(Container $container): LoadedService
    {
        return new LoadedService($this->container === $container ? 'container-aware' : 'wrong');
    }
}
