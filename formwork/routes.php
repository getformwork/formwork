<?php

return [
    'routes' => [
        'index' => [
            'path'   => '/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
        'index.pagination' => [
            'path'   => '/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
        'tag.pagination' => [
            'path'   => '/{page}/tag/{tagName:aln}/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
        'tag' => [
            'path'   => '/{page}/tag/{tagName:aln}/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
        'page.pagination' => [
            'path'   => '/{page}/page/{paginationPage:num}/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
        'page' => [
            'path'   => '/{page}/',
            'action' => 'Formwork\\Controllers\\PageController@load'
        ],
    ]
];
