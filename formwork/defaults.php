<?php

return [
    'panel' => [
        'enabled'        => true,
        'root'           => 'panel',
        'translation'    => 'en',
        'loginAttempts'  => 10,
        'loginResetTime' => 300,
        'logoutRedirect' => 'login',
        'sessionTimeout' => 20,
        'avatarSize'     => 512,
        'colorScheme'    => 'light',
        'paths'          => [
            'accounts' => PANEL_PATH . 'accounts' . DS,
            'logs'     => PANEL_PATH . 'logs' . DS,
            'roles'    => PANEL_PATH . 'roles' . DS,
        ],
    ],
    'backup' => [
        'path'     => ROOT_PATH . 'backup' . DS,
        'maxFiles' => 10,
    ],
    'cache' => [
        'enabled' => false,
        'path'    => ROOT_PATH . 'cache' . DS,
        'time'    => 604800,
    ],
    'charset' => 'utf-8',
    'content' => [
        'path'      => SITE_PATH . 'content' . DS,
        'extension' => '.md',
    ],
    'date' => [
        'format'     => 'm/d/Y',
        'timeFormat' => 'h:i A',
        'timezone'   => 'UTC',
        'weekStarts' => 0,
    ],
    'errors' => [
        'setHandlers' => true,
    ],
    'fields' => [
        'path' => FORMWORK_PATH . 'fields' . DS,
    ],
    'files' => [
        'allowedExtensions' => [
            '.jpg',
            '.jpeg',
            '.png',
            '.gif',
            '.svg',
            '.webp',
            '.pdf',
        ],
    ],
    'images' => [
        'jpegQuality'     => 85,
        'jpegProgressive' => true,
        'pngCompression'  => 6,
        'webpQuality'     => 85,
        'processUploads'  => true,
    ],
    'languages' => [
        'available'     => [],
        'httpPreferred' => false,
    ],
    'metadata' => [
        'setGenerator' => true,
    ],
    'pages' => [
        'index' => 'index',
        'error' => 'error',
    ],
    'parsers' => [
        'usePhpYaml' => 'parse',
    ],
    'routes' => [
        'files' => [
            'panel'  => PANEL_PATH . 'routes.php',
            'system' => FORMWORK_PATH . 'routes.php',
        ],
    ],
    'schemes' => [
        'paths' => [
            'panel'  => PANEL_PATH . 'schemes' . DS,
            'system' => FORMWORK_PATH . 'schemes' . DS,
            'site'   => SITE_PATH . 'schemes' . DS,
        ],
    ],
    'statistics' => [
        'enabled' => true,
    ],
    'templates' => [
        'path'      => SITE_PATH . 'templates' . DS,
        'extension' => '.php',
    ],
    'translations' => [
        'fallback' => 'en',
        'paths'    => [
            'panel'  => PANEL_PATH . 'translations' . DS,
            'system' => FORMWORK_PATH . 'translations' . DS,
        ],
    ],
    'updates' => [
        'backupBefore' => true,
    ],
    'views' => [
        'paths' => [
            'panel'  => PANEL_PATH . 'views' . DS,
            'system' => FORMWORK_PATH . 'views' . DS,
        ],
    ],
];
