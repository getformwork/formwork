<?php

namespace Formwork\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Utils\FileSystem;

/**
 * @since 2.3.0
 */
final class AssetsServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config,
        private UriGenerator $uriGenerator,
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
                'path' => FileSystem::joinPaths($this->config->getString('system.templates.path'), 'assets'),
                'uri'  => $this->uriGenerator->path('/site/templates/assets/'),
            ],
        ]);
    }
}
