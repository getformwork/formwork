<?php

namespace Formwork\Plugins\Controllers;

use Formwork\Controllers\AbstractController;
use Formwork\Controllers\PageController;
use Formwork\Http\FileResponse;
use Formwork\Http\Response;
use Formwork\Plugins\Plugin;
use Formwork\Router\RouteParams;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;

/**
 * @since 2.3.0
 */
final class AssetsController extends AbstractController
{
    public function __construct(
        private Container $container,
        private Plugin $plugin
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * AssetsController@asset action
     */
    public function asset(RouteParams $routeParams): Response
    {
        $path = FileSystem::joinPaths($this->config->get('system.plugins.path'), $this->plugin->id(), 'assets', $routeParams->get('type'), Path::resolve($routeParams->get('file'), '/', DIRECTORY_SEPARATOR));

        if (FileSystem::isFile($path, assertExists: false)) {
            $headers = ($this->request->query()->has('v'))
                ? ['Cache-Control' => 'private, max-age=31536000, immutable']
                : [];
            return new FileResponse($path, headers: $headers, autoEtag: true, autoLastModified: true);
        }

        return $this->forward(PageController::class, 'error');
    }
}
