<?php

namespace Formwork\Services\Loaders;

use Formwork\Config;
use Formwork\Languages\Languages;
use Formwork\Pages\Site;
use Formwork\Pages\Templates\TemplateFactory;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

class SiteServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(protected Config $config, protected Languages $languages, protected Schemes $schemes)
    {

    }

    public function load(Container $container): Site
    {
        $container->define(TemplateFactory::class);
        $this->schemes->loadFromPath($this->config->get('system.schemes.paths.site'));
        $config = Yaml::parseFile(ROOT_PATH . '/site/config/site.yaml');

        return $container->build(Site::class, ['data' => [
            'path'      => $this->config->get('system.content.path'),
            'languages' => $this->languages,
        ] + $config]);

    }

    /**
     * @param Site $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->load();
    }
}
