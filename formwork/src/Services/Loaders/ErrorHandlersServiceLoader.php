<?php

namespace Formwork\Services\Loaders;

use Formwork\ErrorHandlers;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

class ErrorHandlersServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function load(Container $container): ErrorHandlers
    {
        return $container->build(ErrorHandlers::class);
    }

    /**
     * @param ErrorHandlers $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->setHandlers();
    }
}
