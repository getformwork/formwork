<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Languages\Languages;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Site;

class SiteServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(protected Config $config, protected Languages $languages, protected Schemes $schemes)
    {
    }

    public function load(Container $container): Site
    {
        $this->schemes->loadFromPath($this->config->get('system.schemes.paths.site'));
        $config = $this->config->get('site');

        return $container->build(Site::class, ['data' => [
            ...$config,
            'contentPath' => $this->config->get('system.pages.path'),
            'languages'   => $this->languages,
        ]]);
    }

    /**
     * @param Site $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->load();
    }
}
