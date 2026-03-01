<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Parsers\Php;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Utils\FileSystem;

final class ConfigServiceLoader implements ServiceLoaderInterface
{
    /**
     * Config cache path
     */
    private const string CACHE_PATH = ROOT_PATH . '/cache/config/';

    public function __construct(
        private Request $request,
    ) {}

    public function load(Container $container): Config
    {
        $cacheFile = FileSystem::joinPaths(self::CACHE_PATH, "config.{$this->request->host()}.php");

        if (!FileSystem::isDirectory(self::CACHE_PATH, assertExists: false)) {
            FileSystem::createDirectory(self::CACHE_PATH, recursive: true);
        }

        if (
            FileSystem::exists($cacheFile)
            && !FileSystem::directoryModifiedSince(ROOT_PATH . '/site/config/', FileSystem::lastModifiedTime($cacheFile))
            && !FileSystem::directoryModifiedSince(SYSTEM_PATH . '/config/', FileSystem::lastModifiedTime($cacheFile))
        ) {
            $config = new Config(require $cacheFile, resolved: true);
        } else {
            $config = new Config();

            $config->loadFromPath(SYSTEM_PATH . '/config/');
            $config->loadFromPath(ROOT_PATH . '/site/config/');

            if (FileSystem::isDirectory($pluginsConfigPath = ROOT_PATH . '/site/config/plugins/', assertExists: false)) {
                $config->loadFromPath($pluginsConfigPath, 'plugins');
            }

            $config->resolve([
                '%ROOT_PATH%'   => ROOT_PATH,
                '%SYSTEM_PATH%' => SYSTEM_PATH,
            ]);

            if (PHP_SAPI !== 'cli') {
                Php::encodeToFile($config->toArray(), $cacheFile);
            }
        }

        date_default_timezone_set($config->get('system.date.timezone'));

        $this->request->session()->setPath($config->get('system.session.path'));
        $this->request->session()->setDuration($config->get('system.session.duration'));

        return $config;
    }

    /**
     * Clear config cache
     *
     * @internal
     */
    public static function clearCache(): void
    {
        FileSystem::delete(self::CACHE_PATH, recursive: true);
        FileSystem::createDirectory(self::CACHE_PATH, recursive: true);
    }
}
