<?php

namespace Formwork\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

/**
 * @since 2.3.0
 */
final class AssetsServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config,
        private Site $site,
    ) {}

    public function load(Container $container): Assets
    {
        return new Assets();
    }

    /**
     * @param Assets $service
     */
    public function onResolved(object $service, Container $container): void
    {
        // Configure template assets namespace
        $service->setResolutionPaths([
            'template' => [
                'path' => $this->config->getString('system.templates.path') . '/assets',
                'uri'  => $this->site->uri('/site/templates/assets/', includeLanguage: false),
            ],
        ]);
    }
}
