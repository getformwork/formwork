<?php

return [
    'routes' => [
        'loaded' => [
            'path'             => '/loaded/{id:digits}',
            'action'           => 'Formwork\Tests\Unit\Router\RouteControllerFixture@handle',
            'actionParameters' => ['fromRouteFile' => 'route'],
            'where'            => ['id' => ['42']],
            'methods'          => ['POST'],
            'prefix'           => '/api',
        ],
    ],
    'filters' => [
        'loaded-filter' => [
            'action'  => 'Formwork\Tests\Unit\Router\RouteFilterFixture@handle',
            'methods' => ['GET'],
            'types'   => ['HTTP'],
            'prefix'  => '/api',
        ],
    ],
];
