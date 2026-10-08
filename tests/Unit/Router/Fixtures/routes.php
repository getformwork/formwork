<?php

return [
    'routes' => [
        'loaded' => [
            'path'             => '/loaded/{id:digits}',
            'action'           => 'Formwork\Tests\Unit\Router\RouteControllerFixture@handle',
            'actionParameters' => ['fromRouteFile' => 'route', 'shared' => 'route'],
            'where'            => ['id' => ['42']],
            'methods'          => ['POST'],
            'prefix'           => '/api',
        ],
        'unprefixed' => [
            'path'    => '/unprefixed',
            'action'  => 'Formwork\Tests\Unit\Router\RouteControllerFixture@handle',
            'types'   => ['XHR'],
            'methods' => ['GET', 'POST'],
        ],
    ],
    'filters' => [
        'loaded-filter' => [
            'action'  => 'Formwork\Tests\Unit\Router\RouteFilterFixture@handle',
            'methods' => ['GET'],
            'types'   => ['HTTP'],
            'prefix'  => '/api',
        ],
        'unprefixed-filter' => [
            'action'  => 'Formwork\Tests\Unit\Router\RouteFilterFixture@handle',
            'methods' => ['POST', 'PUT'],
            'types'   => ['XHR'],
        ],
    ],
];
