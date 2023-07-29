<?php

namespace Formwork\Services\Loaders;

use Formwork\Config;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;

class ConfigServiceLoader implements ServiceLoaderInterface
{
    public function load(Container $container): Config
    {
        $config = new Config();

        $config->loadFromPath(SYSTEM_PATH . '/config/');
        $config->loadFromPath(ROOT_PATH . '/site/config/');

        $config->resolve([
            '%ROOT_PATH%'   => ROOT_PATH,
            '%SYSTEM_PATH%' => SYSTEM_PATH,
        ]);

        date_default_timezone_set($config->get('system.date.timezone'));

        return $config;
    }
}
