<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Languages\Languages;
use Formwork\Languages\LanguagesFactory;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Translations\Translations;

final class LanguagesServiceLoader implements ServiceLoaderInterface
{
    public function __construct(
        private Config $config,
        private Translations $translations,
        private LanguagesFactory $languagesFactory,
    ) {}

    public function load(Container $container): Languages
    {
        $languages = $this->languagesFactory->make($this->config->get('site.languages'));

        if (($currentTranslation = $languages->current() ?? $languages->default()) !== null) {
            $this->translations->setCurrent($currentTranslation->code());
        }

        return $languages;
    }
}
