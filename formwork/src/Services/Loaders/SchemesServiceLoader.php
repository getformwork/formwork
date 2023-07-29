<?php

namespace Formwork\Services\Loaders;

use Formwork\Config;
use Formwork\Fields\FieldFactory;
use Formwork\Languages\Languages;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

class SchemesServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(protected Config $config, protected Languages $languages)
    {

    }

    public function load(Container $container): object
    {
        $container->define(FieldFactory::class);

        return $container->build(Schemes::class);
    }

    /**
     * @param Schemes $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->loadFromPath($this->config->get('system.schemes.paths.system'));
    }
}
