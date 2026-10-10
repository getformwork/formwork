<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use RuntimeException;

final class ThrowingLoader implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedService
    {
        throw new RuntimeException('loader failed');
    }
}
