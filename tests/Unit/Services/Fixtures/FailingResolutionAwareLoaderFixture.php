<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use RuntimeException;

final class FailingResolutionAwareLoaderFixture implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public function load(Container $container): LoadedServiceFixture
    {
        self::$loadCount++;
        return new LoadedServiceFixture();
    }

    public function onResolved(object $service, Container $container): void
    {
        throw new RuntimeException('resolution callback failed');
    }
}
