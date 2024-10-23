<?php

namespace Formwork\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;

class AssetController extends AbstractController
{
    public function load(RouteParams $routeParams): FileResponse
    {
        $path = FileSystem::joinPaths($this->config->get('system.images.processPath'), $routeParams->get('id'), $routeParams->get('name'));

        if (FileSystem::isFile($path)) {
            return new FileResponse($path);
        }

        throw new FileNotFoundException('Cannot find asset');
    }
}
