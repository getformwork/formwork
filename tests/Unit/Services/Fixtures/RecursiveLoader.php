<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class RecursiveLoader implements ServiceLoaderInterface
{
    public function __construct(private string $other) {}

    public function load(Container $container): LoadedService
    {
        return $container->get($this->other);
    }
}
