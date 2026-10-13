<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class InvalidReturnLoader implements ServiceLoaderInterface
{
    public function load(Container $container): object
    {
        return 'invalid';
    }
}
