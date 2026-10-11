<?php

namespace Formwork\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;

final class FilesController extends AbstractController
{
    /**
     * FilesController@file action
     */
    public function file(RouteParams $routeParams): Response
    {
        $filesPath = $this->config->get('system.files.paths.site');
        $path = FileSystem::joinPaths($filesPath, $routeParams->get('name'));

        if (Path::isRelativeTo($path, $filesPath) && FileSystem::isFile($path, assertExists: false)) {
            return new FileResponse($path);
        }

        return $this->forward(PageController::class, 'error');
    }
}
