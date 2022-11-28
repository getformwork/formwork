<?php

return [
    'panel' => [
        'enabled'          => true,
        'root'             => 'panel',
        'translation'      => 'en',
        'login_attempts'   => 10,
        'login_reset_time' => 300,
        'logout_redirect'  => 'login',
        'session_timeout'  => 20,
        'avatar_size'      => 512,
        'color_scheme'     => 'light',
        'paths'            => [
            'accounts' => PANEL_PATH . 'accounts' . DS,
            'logs'     => PANEL_PATH . 'logs' . DS
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
    'errors' => [
        'set_handlers' => true
    ],
    'fields' => [
        'path' => FORMWORK_PATH . 'fields' . DS
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
        'error' => 'error'
    ],
    'parsers' => [
        'use_php_yaml' => 'parse'
    ],
    'routes' => [
        'files' => [
            'panel'  => PANEL_PATH . 'routes.php',
            'system' => FORMWORK_PATH . 'routes.php'
        ]
    ],
    'schemes' => [
        'paths' => [
            'panel'  => PANEL_PATH . 'schemes' . DS,
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
            'panel'  => PANEL_PATH . 'translations' . DS,
            'system' => FORMWORK_PATH . 'translations' . DS
        ]
    ],
    'updates' => [
        'backup_before' => true
    ],
    'views' => [
        'paths' => [
            'panel'  => PANEL_PATH . 'views' . DS,
            'system' => FORMWORK_PATH . 'views' . DS
        ]
    ]
];
