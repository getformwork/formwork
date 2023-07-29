<?php

namespace Formwork\Services;

interface ResolutionAwareServiceLoaderInterface extends ServiceLoaderInterface
{
    public function onResolved(object $service, Container $container): void;
}
