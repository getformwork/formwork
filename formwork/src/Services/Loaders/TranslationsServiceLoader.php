<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Languages\Languages;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Translations\Translations;

class TranslationsServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(protected Config $config, protected Languages $languages)
    {
    }

    public function load(Container $container): object
    {
        return $container->build(Translations::class);
    }

    /**
     * @param Translations $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->loadFromPath($this->config->get('system.translations.paths.system'));
        $service->setCurrent($this->languages->current() ?? $this->config->get('system.translations.fallback'));
    }
}
