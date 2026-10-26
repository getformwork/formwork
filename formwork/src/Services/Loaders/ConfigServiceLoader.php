<?php

namespace Formwork\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Parsers\Php;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

final class ConfigServiceLoader implements ServiceLoaderInterface
{
    /**
     * Config cache path, relative to the installation root
     */
    private const string CACHE_PATH = '/cache/config/';

    public function __construct(
        private Request $request,
        private App $app,
    ) {}

    public function load(Container $container): Config
    {
        $cacheFile = $this->getConfigCacheFile();

        if ($this->validateConfigCacheFile($cacheFile)) {
            $data = require $cacheFile;
        } else {
            $config = new Config();

            $config->loadFromPath($this->app->systemPath() . '/config/');
            $config->loadFromPath($this->app->rootPath() . '/site/config/');

            if (FileSystem::isDirectory($pluginsConfigPath = $this->app->rootPath() . '/site/config/plugins/', assertExists: false)) {
                $config->loadFromPath($pluginsConfigPath, 'plugins');
            }

            $config->resolve([
                '%ROOT_PATH%'   => $this->app->rootPath(),
                '%SYSTEM_PATH%' => $this->app->systemPath(),
            ]);

            $data = $config->toArray();

            if (PHP_SAPI !== 'cli' && $cacheFile !== null) {
                Php::encodeToFile($data, $cacheFile);
            }
        }

        // Replace deprecated options after caching, so that deprecations are triggered on every request
        $config = new Config($this->replaceDeprecatedOptions($data), resolved: true);

        date_default_timezone_set($config->getString('system.date.timezone'));

        $this->request->session()->setPath($config->getString('system.session.path'));
        $this->request->session()->setDuration($config->getInt('system.session.duration'));

        return $config;
    }

    /**
     * Clear config cache
     *
     * @internal
     */
    public static function clearCache(string $rootPath): void
    {
        $cachePath = $rootPath . self::CACHE_PATH;

        FileSystem::delete($cachePath, recursive: true);
        FileSystem::createDirectory($cachePath, recursive: true);
    }

    private function getConfigCacheFile(): ?string
    {
        try {
            $host = $this->request->host();
        } catch (UnexpectedValueException) {
            return null;
        }

        $cachePath = $this->app->rootPath() . self::CACHE_PATH;

        $cacheFile = FileSystem::joinPaths($cachePath, "config.{$host}.php");

        if (!FileSystem::isDirectory($cachePath, assertExists: false)) {
            FileSystem::createDirectory($cachePath, recursive: true);
        }

        return $cacheFile;
    }

    private function validateConfigCacheFile(?string $cacheFile): bool
    {
        return $cacheFile !== null
            && FileSystem::exists($cacheFile)
            && !FileSystem::directoryModifiedSince($this->app->rootPath() . '/site/config/', FileSystem::lastModifiedTime($cacheFile))
            && !FileSystem::directoryModifiedSince($this->app->systemPath() . '/config/', FileSystem::lastModifiedTime($cacheFile));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @todo Remove in Formwork 3.0
     */
    private function replaceDeprecatedOptions(array $data): array
    {
        // Options of the pages cache before Formwork 2.4.0
        foreach (['enabled' => 'enabled', 'path' => 'path', 'time' => 'ttl'] as $deprecated => $replacement) {
            if (!isset($data['system']['cache']) || !is_array($data['system']['cache']) || !array_key_exists($deprecated, $data['system']['cache']) || is_array($data['system']['cache'][$deprecated])) {
                continue;
            }

            trigger_error(sprintf('The "system.cache.%s" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.%s" instead.', $deprecated, $replacement), E_USER_DEPRECATED);

            if (!isset($data['system']['cache']['pages']) || !is_array($data['system']['cache']['pages'])) {
                $data['system']['cache']['pages'] = [];
            }

            $data['system']['cache']['pages'][$replacement] = $data['system']['cache'][$deprecated];
            unset($data['system']['cache'][$deprecated]);
        }

        // Handler of the pages cache before Formwork 2.4.0
        if (isset($data['system']['cache']['namespaces']) && is_array($data['system']['cache']['namespaces'])) {
            $namespaces = $data['system']['cache']['namespaces'];
            unset($data['system']['cache']['namespaces']);

            trigger_error('The "system.cache.namespaces" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.handler" instead.', E_USER_DEPRECATED);

            if (isset($namespaces['pages']['handler']) && is_string($namespaces['pages']['handler'])) {
                if (!isset($data['system']['cache']['pages']) || !is_array($data['system']['cache']['pages'])) {
                    $data['system']['cache']['pages'] = [];
                }

                $data['system']['cache']['pages']['handler'] = $namespaces['pages']['handler'];
            }
        }

        return $data;
    }
}
