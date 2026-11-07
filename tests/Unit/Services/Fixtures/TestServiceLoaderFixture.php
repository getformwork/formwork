<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

final class TestServiceLoaderFixture implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public static int $resolvedCount = 0;

    public function __construct(private SharedDependencyFixture $dependency) {}

    public function load(Container $container): LoadedServiceFixture
    {
        self::$loadCount++;
        return new LoadedServiceFixture($this->dependency instanceof SharedDependencyFixture ? 'loaded' : 'wrong');
    }

    public function onResolved(object $service, Container $container): void
    {
        self::$resolvedCount++;
    }
}
