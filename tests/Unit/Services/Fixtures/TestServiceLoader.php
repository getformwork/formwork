<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

final class TestServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public static int $resolvedCount = 0;

    public function __construct(private SharedDependency $dependency) {}

    public function load(Container $container): LoadedService
    {
        self::$loadCount++;
        return new LoadedService($this->dependency instanceof SharedDependency ? 'loaded' : 'wrong');
    }

    public function onResolved(object $service, Container $container): void
    {
        self::$resolvedCount++;
    }
}
