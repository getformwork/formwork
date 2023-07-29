<?php

namespace Formwork\Services;

interface ServiceLoaderInterface
{
    public function load(Container $container): object;
}
