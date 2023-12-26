<?php

use Formwork\Config\Config;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Languages\Languages;
use Formwork\Router\Router;
use Formwork\Utils\Str;

return [
    'routes' => [
        'index' => [
            'path'   => '/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
        'index.pagination' => [
            'path'   => '/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
        'assets' => [
            'path'   => '/assets/{id}/{name}/',
            'action' => 'Formwork\\Controllers\\AssetController@load',
        ],
        'tag.pagination' => [
            'path'   => '/{page}/tag/{tagName:aln}/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
        'tag' => [
            'path'   => '/{page}/tag/{tagName:aln}/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
        'page.pagination' => [
            'path'   => '/{page}/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
        'page' => [
            'path'   => '/{page}/',
            'action' => 'Formwork\\Controllers\\PageController@load',
        ],
    ],

    'filters' => [
        'language' => [
            'action' => function (Config $config, Request $request, Router $router, Languages $languages) {
                if (($requested = $languages->requested()) !== null) {
                    $router->setRequest(Str::removeStart($router->request(), '/' . $requested));
                } elseif (($preferred = $languages->preferred()) !== null) {
                    // Don't redirect if we are in Panel
                    if (!Str::startsWith($router->request(), '/' . $config->get('system.panel.root'))) {
                        return new RedirectResponse($request->root() . $preferred . $router->request());
                    }
                }
            },
        ],
    ],
];
