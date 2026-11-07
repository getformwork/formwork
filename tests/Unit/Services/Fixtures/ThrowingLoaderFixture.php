<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use RuntimeException;

final class ThrowingLoaderFixture implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedServiceFixture
    {
        throw new RuntimeException('loader failed');
    }
}
