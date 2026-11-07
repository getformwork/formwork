<?php

namespace Formwork\Services;

/**
 * @since 2.4.0
 */
interface DefinitionAwareServiceLoaderInterface extends ServiceLoaderInterface
{
    /**
     * Called when the service is defined with the loader, without instantiating the loader or resolving the service
     */
    public static function onDefined(Container $container): void;
}
