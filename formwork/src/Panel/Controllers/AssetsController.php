<?php

namespace Formwork\Panel\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

final class AssetsController extends AbstractController
{
    /**
     * AssetsController@asset action
     */
    public function asset(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.panel.paths.assets'), $routeParams->get('type'), $routeParams->get('file'));

        if (FileSystem::isFile($path, assertExists: false)) {
            $headers = (
                $this->request->query()->has('v')
                || $routeParams->get('type') === 'icons'

                // Panel js chunks contain an hash in their filename, they can be cached with immutability
                || ($routeParams->get('type') === 'js' && Str::startsWith($routeParams->get('file'), 'chunks/'))
            )
                ? ['Cache-Control' => 'private, max-age=31536000, immutable']
                : [];
            return new FileResponse($path, headers: $headers, autoEtag: true, autoLastModified: true);
        }

        return $this->forward(ErrorsController::class, 'notFound');
    }
}
