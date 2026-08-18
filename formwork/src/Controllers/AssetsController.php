<?php

namespace Formwork\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;

final class AssetsController extends AbstractController
{
    /**
     * AssetsController@asset action
     */
    public function asset(RouteParams $routeParams): Response
    {
        if (!$routeParams->has('type')) {
            trigger_error('The "assets" route without a "type" parameter is deprecated since Formwork 2.3.2 and will be removed in a future version', E_USER_DEPRECATED);
            return $this->redirect($this->router->rewrite(['type' => 'images']), ResponseStatus::MovedPermanently);
        }

        $path = FileSystem::joinPaths($this->config->getString('system.images.processPath'), $routeParams->get('id'), $routeParams->get('name'));

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
        $path = FileSystem::joinPaths($this->config->getString('system.templates.path'), 'assets', Path::resolve($routeParams->get('file'), '/', DIRECTORY_SEPARATOR));

        if (FileSystem::isFile($path, assertExists: false)) {
            $headers = $this->request->query()->has('v')
                ? ['Cache-Control' => 'private, max-age=31536000, immutable']
                : [];
            return new FileResponse($path, headers: $headers, autoEtag: true, autoLastModified: true);
        }

        return $this->forward(PageController::class, 'error');
    }
}
