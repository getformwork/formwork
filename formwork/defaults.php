<?php

return [
    'admin' => [
        'enabled'          => true,
        'root'             => 'admin',
        'lang'             => 'en',
        'login_attempts'   => 10,
        'login_reset_time' => 300,
        'logout_redirect'  => 'login',
        'session_timeout'  => 20,
        'avatar_size'      => 512,
        'color_scheme'     => 'light',
        'paths'            => [
            'accounts' => ADMIN_PATH . 'accounts' . DS,
            'logs'     => ADMIN_PATH . 'logs' . DS
        ]
    ],
    'backup' => [
        'path'      => ROOT_PATH . 'backup' . DS,
        'max_files' => 10
    ],
    'cache' => [
        'enabled' => false,
        'path'    => ROOT_PATH . 'cache' . DS,
        'time'    => 604800
    ],
    'charset' => 'utf-8',
    'content' => [
        'path'      => SITE_PATH . 'content' . DS,
        'extension' => '.md'
    ],
    'date' => [
        'format'      => 'm/d/Y',
        'time_format' => 'h:i A',
        'timezone'    => 'UTC',
        'week_starts' => 0
    ],
    'files' => [
        'allowed_extensions' => [
            '.jpg',
            '.jpeg',
            '.png',
            '.gif',
            '.svg',
            '.webp',
            '.pdf'
        ]
    ],
    'images' => [
        'jpeg_quality'     => 85,
        'jpeg_progressive' => true,
        'png_compression'  => 6,
        'webp_quality'     => 85,
        'process_uploads'  => true
    ],
    'languages' => [
        'available'      => [],
        'http_preferred' => false
    ],
    'metadata' => [
        'set_generator' => true
    ],
    'pages' => [
        'index' => 'index',
        'error' => '404'
    ],
    'parsers' => [
        'use_php_yaml' => 'parse'
    ],
    'routes' => [
        'files' => [
            'admin'  => ADMIN_PATH . 'routes.php',
            'system' => FORMWORK_PATH . 'routes.php'
        ]
    ],
    'schemes' => [
        'paths' => [
            'admin'  => ADMIN_PATH . 'schemes' . DS,
            'config' => CONFIG_PATH . 'schemes' . DS,
            'pages'  => SITE_PATH . 'schemes' . DS
        ]
    ],
    'statistics' => [
        'enabled' => true
    ],
    'templates' => [
        'path'      => SITE_PATH . 'templates' . DS,
        'extension' => '.php'
    ],
    'translations' => [
        'fallback' => 'en',
        'paths'    => [
            'admin'  => ADMIN_PATH . 'translations' . DS,
            'system' => FORMWORK_PATH . 'translations' . DS
        ]
    ],
    'updates' => [
        'backup_before' => true
    ],
    'views' => [
        'paths' => [
            'admin'  => ADMIN_PATH . 'views' . DS,
            'system' => FORMWORK_PATH . 'views' . DS
        ]
    ]
];
