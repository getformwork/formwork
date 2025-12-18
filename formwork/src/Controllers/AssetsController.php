<?php

namespace Formwork\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;

final class AssetsController extends AbstractController
{
    /**
     * AssetsController@asset action
     */
    public function asset(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.images.processPath'), $routeParams->get('id'), $routeParams->get('name'));

        if (FileSystem::isFile($path, assertExists: false)) {
            return new FileResponse($path, headers: ['Cache-Control' => 'private, max-age=31536000, immutable'], autoEtag: true, autoLastModified: true);
        }

        return $this->forward(PageController::class, 'error');
    }

    /**
     * AssetsController@template action
     */
    public function template(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.templates.path'), 'assets', $routeParams->get('file'));

        if (FileSystem::isFile($path, assertExists: false)) {
            $headers = $this->request->query()->has('v')
                ? ['Cache-Control' => 'private, max-age=31536000, immutable']
                : [];
            return new FileResponse($path, headers: $headers, autoEtag: true, autoLastModified: true);
        }

        return $this->forward(PageController::class, 'error');
    }
}
