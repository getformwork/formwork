<?php

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Translations\Translation;

return fn(App $app, Site $site, Translation $translation) => [
    'dashboard' => [
        'label'       => $translation->translate('panel.dashboard.dashboard'),
        'uri'         => '/dashboard/',
        'permissions' => 'panel.dashboard',
        'badge'       => null,
        'icon'        => 'home',
    ],
    'pages' => [
        'label'       => $translation->translate('panel.pages.pages'),
        'uri'         => '/pages/',
        'permissions' => 'panel.pages',
        'badge'       => $site->descendants()->count(),
        'icon'        => 'pages',
    ],
    'files' => [
        'label'       => $translation->translate('panel.files.files'),
        'uri'         => '/files/',
        'permissions' => 'panel.files',
        'badge'       => null,
        'icon'        => 'files',
    ],
    'statistics' => [
        'label'       => $translation->translate('panel.statistics.statistics'),
        'uri'         => '/statistics/',
        'permissions' => 'panel.statistics',
        'badge'       => null,
        'icon'        => 'chart-line',
    ],
    'users' => [
        'label'       => $translation->translate('panel.users.users'),
        'uri'         => '/users/',
        'permissions' => 'panel.users',
        'badge'       => $site->users()->count(),
        'icon'        => 'users',
    ],
    'options' => [
        'label'       => $translation->translate('panel.options.options'),
        'uri'         => '/options/',
        'permissions' => 'panel.options',
        'badge'       => null,
        'icon'        => 'gear',
    ],
    'tools' => [
        'label'       => $translation->translate('panel.tools.tools'),
        'uri'         => '/tools/',
        'permissions' => 'panel.tools',
        'badge'       => null,
        'icon'        => 'toolbox',
    ],
    'plugins' => [
        'label'       => $translation->translate('panel.plugins.plugins'),
        'uri'         => '/plugins/',
        'permissions' => 'panel.plugins',
        'badge'       => $app->plugins()->count(),
        'icon'        => 'puzzle-piece',
        'visible'     => !$app->plugins()->isEmpty(),
    ],
    'logout' => [
        'label'       => $translation->translate('panel.login.logout'),
        'uri'         => '/logout/',
        'permissions' => null,
        'badge'       => null,
    ],
];
