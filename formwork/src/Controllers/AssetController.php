<?php

namespace Formwork\Controllers;

use Formwork\Config;
use Formwork\Http\FileResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;

class AssetController
{
    public function load(RouteParams $params, Config $config): FileResponse
    {
        $path = FileSystem::joinPaths($config->get('system.images.processPath'), $params->get('id'), $params->get('name'));

        if (FileSystem::isFile($path)) {
            return new FileResponse($path);
        }

        throw new FileNotFoundException();
    }
}
