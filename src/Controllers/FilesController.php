<?php

namespace Formwork\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;

final class FilesController extends AbstractController
{
    /**
     * FilesController@file action
     */
    public function file(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->getString('system.files.paths.site'), $routeParams->get('name'));

        if (FileSystem::isFile($path, assertExists: false)) {
            return new FileResponse($path);
        }

        return $this->forward(PageController::class, 'error');
    }
}
