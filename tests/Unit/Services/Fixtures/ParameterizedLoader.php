<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

final class ParameterizedLoader implements ServiceLoaderInterface
{
    public function __construct(private string $label) {}

    public function load(Container $container): LoadedService
    {
        return new LoadedService($this->label);
    }
}
