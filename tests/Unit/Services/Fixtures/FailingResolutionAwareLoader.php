<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use RuntimeException;

final class FailingResolutionAwareLoader implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public function load(Container $container): LoadedService
    {
        self::$loadCount++;
        return new LoadedService();
    }

    public function onResolved(object $service, Container $container): void
    {
        throw new RuntimeException('resolution callback failed');
    }
}
