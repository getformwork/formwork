<?php

use Formwork\Config\Config;
use Formwork\ErrorHandlers;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Languages\Languages;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
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
            'path'   => '/page/{paginationPage:num}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'assets' => [
            'path'   => '/assets/{id}/{name}/',
            'action' => 'Formwork\Controllers\AssetController@load',
        ],
        'tag.pagination' => [
            'path'   => '/{page}/tag/{tagName:aln}/page/{paginationPage:num}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'tag' => [
            'path'   => '/{page}/tag/{tagName:aln}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'page.pagination' => [
            'path'   => '/{page}/page/{paginationPage:num}/',
            'action' => 'Formwork\Controllers\PageController@load',
        ],
        'page' => [
            'path'    => '/{page}/',
            'action'  => 'Formwork\Controllers\PageController@load',
            'methods' => ['GET', 'POST'],
        ],
    ],

    'filters' => [
        'request.validateSize' => [
            'action' => static function (Config $config, Request $request, Router $router, ErrorHandlers $errorHandlers) {
                if ($config->get('system.panel.enabled') && $router->requestHasPrefix($config->get('system.panel.root'))) {
                    return;
                }

                // Validate HTTP request Content-Length according to `post_max_size` directive
                if ($request->contentLength() !== null) {
                    $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size') ?: '0');

                    if ($request->contentLength() > $maxSize && $maxSize > 0) {
                        $errorHandlers->displayErrorPage(ResponseStatus::PayloadTooLarge);
                    }
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'request.validateCsrf' => [
            'action' => static function (Config $config, Request $request, Router $router, CsrfToken $csrfToken, ErrorHandlers $errorHandlers) {
                if ($config->get('system.panel.enabled') && $router->requestHasPrefix($config->get('system.panel.root'))) {
                    return;
                }

                $tokenName = (string) $request->input()->get('csrf-token-name', 'site');
                $token = (string) $request->input()->get('csrf-token');

                if (!($csrfToken->validate($tokenName, $token))) {
                    $csrfToken->destroy($tokenName);
                    $errorHandlers->displayErrorPage(ResponseStatus::Forbidden);
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'language' => [
            'action' => function (Config $config, Request $request, Router $router, Languages $languages) {
                if (($requested = $languages->requested()) !== null) {
                    $router->setRequest(Str::removeStart($router->request(), '/' . $requested));
                } elseif (($preferred = $languages->preferred()) !== null) {
                    // Don't redirect if we are in Panel
                    if ($config->get('system.panel.enabled') && $router->requestHasPrefix($config->get('system.panel.root'))) {
                        return;
                    }
                    return new RedirectResponse($request->root() . $preferred . $router->request());
                }
            },
        ],
    ],
];
