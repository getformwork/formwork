<?php

namespace Formwork\Services;

interface ServiceLoaderInterface
{
    /**
     * Load a service
     */
    public function load(Container $container): object;
}
