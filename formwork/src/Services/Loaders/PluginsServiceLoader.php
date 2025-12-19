<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Plugins\PluginFactory;
use Formwork\Plugins\Plugins;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;

final class PluginsServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function load(Container $container): Plugins
    {
        $container->define(PluginFactory::class);
        return $container->build(Plugins::class);
    }

    /**
     * @param Plugins $service
     */
    public function onResolved(object $service, Container $container): void
    {
        if (!$this->config->get('system.plugins.enabled')) {
            return;
        }

        $pluginsPath = $this->config->get('system.plugins.path');

        if (!FileSystem::isDirectory($pluginsPath, assertExists: false)) {
            return;
        }

        foreach (FileSystem::listDirectories($pluginsPath) as $directory) {
            $id = Str::toCamelCase($directory);
            $path = FileSystem::joinPaths($pluginsPath, $directory);

            try {
                $service->load($id, $path);
            } catch (RuntimeException) {
                // Ignore invalid plugins
            }
        }
    }
}
