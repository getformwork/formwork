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
            $config = new Config(require $cacheFile, resolved: true);
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

            if (PHP_SAPI !== 'cli' && $cacheFile !== null) {
                Php::encodeToFile($config->toArray(), $cacheFile);
            }
        }

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
}
