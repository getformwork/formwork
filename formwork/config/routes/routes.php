<?php

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

return [
    'routes' => [
        'index' => [
            'path'    => '/',
            'action'  => 'Formwork\Controllers\PageController@load',
            'methods' => ['GET', 'POST'],
        ],
        'index.pagination' => [
            'path'   => '/page/{paginationPage:number}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'assets' => [
            /** @todo require the type param in Formwork >= 3.0.0 */
            'path'  => '/assets/{type}?/{id}/{name}/',
            'where' => [
                'type' => ['images', null],
            ],
            'action' => 'Formwork\Controllers\AssetsController@asset',
        ],
        'assets.template' => [
            'path'   => '/site/templates/assets/{file:all}/',
            'action' => 'Formwork\Controllers\AssetsController@template',
        ],
        'files' => [
            'path'   => '/files/{name}/',
            'action' => 'Formwork\Controllers\FilesController@file',
        ],
        'taxonomy.pagination' => [
            'path'  => '/{page:all}/{taxonomy}/{taxonomyTerm:slug}/page/{paginationPage:number}/',
            'where' => [
                'taxonomy' => fn($value, Site $site) => in_array($value, Arr::from($site->get('taxonomies')), true),
            ],
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'taxonomy' => [
            'path'  => '/{page:all}/{taxonomy}/{taxonomyTerm:slug}/',
            'where' => [
                'taxonomy' => fn($value, Site $site) => in_array($value, Arr::from($site->get('taxonomies')), true),
            ],
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'page.pagination' => [
            'path'   => '/{page:all}/page/{paginationPage:number}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'page' => [
            'path'    => '/{page:all}/',
            'action'  => 'Formwork\Controllers\PageController@load',
            'methods' => ['GET', 'POST'],
        ],
    ],

    'filters' => [
        'request.validateSize' => [
            'action' => static function (Config $config, Request $request, Router $router, ErrorsControllerInterface $errorsController) {
                if ($config->getBool('system.panel.enabled') && $router->requestHasPrefix($config->getString('system.panel.root'))) {
                    return;
                }

                // Validate HTTP request Content-Length according to `post_max_size` directive
                if ($request->contentLength() !== null) {
                    $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size') ?: '0');

                    if ($request->contentLength() > $maxSize && $maxSize > 0) {
                        return $errorsController->error(ResponseStatus::PayloadTooLarge);
                    }
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'request.validateCsrf' => [
            'action' => static function (Config $config, Request $request, Router $router, CsrfToken $csrfToken, ErrorsControllerInterface $errorsController) {
                if ($config->getBool('system.panel.enabled') && $router->requestHasPrefix($config->getString('system.panel.root'))) {
                    // CSRF validation is handled by a separate filter in the panel routes
                    return;
                }

                $tokenName = (string) $request->input()->get('csrf-token-name', 'site');
                $token = (string) $request->input()->get('csrf-token');

                if (!($csrfToken->validate($tokenName, $token))) {
                    $csrfToken->destroy($tokenName);
                    return $errorsController->error(ResponseStatus::Forbidden);
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'language' => [
            'action' => function (Config $config, Request $request, Router $router, Site $site) {
                if (!$site->languages()->hasMultiple()) {
                    return;
                }
                if (($requested = $site->languages()->requested()) !== null) {
                    // @phpstan-ignore method.internal
                    $router->setRequest(Str::removeStart($router->request(), '/' . $requested));
                } elseif (($preferred = $site->languages()->preferred()) !== null) {
                    // Don't redirect if we are in Panel
                    if ($config->getBool('system.panel.enabled') && $router->requestHasPrefix($config->getString('system.panel.root'))) {
                        return;
                    }
                    return new RedirectResponse($request->root() . $preferred . $router->request());
                }
            },
        ],
    ],
];
