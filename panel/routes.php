<?php

use Formwork\Panel\Security\CSRFToken;
use Formwork\Formwork;
use Formwork\Response\JSONResponse;
use Formwork\Response\RedirectResponse;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Session;

return [
    'routes' => [
        'panel.index' => [
            'path'   => '/',
            'action' => fn () => new RedirectResponse(Formwork::instance()->panel()->uri('/dashboard/'))
        ],
        'panel.login' => [
            'path'    => '/login/',
            'action'  => 'Formwork\\Panel\\Controllers\\AuthenticationController@login',
            'methods' => ['GET', 'POST']
        ],
        'panel.logout' => [
            'path'   => '/logout/',
            'action' => 'Formwork\\Panel\\Controllers\\AuthenticationController@logout'
        ],
        'panel.backup.make' => [
            'path'    => '/backup/make/',
            'action'  => 'Formwork\\Panel\\Controllers\\BackupController@make',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'panel.backup.download' => [
            'path'    => '/backup/download/{backup}/',
            'action'  => 'Formwork\\Panel\\Controllers\\BackupController@download',
            'methods' => ['POST']
        ],
        'panel.cache.clear' => [
            'path'    => '/cache/clear/',
            'action'  => 'Formwork\\Panel\\Controllers\\CacheController@clear',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'panel.dashboard' => [
            'path'   => '/dashboard/',
            'action' => 'Formwork\Panel\Controllers\DashboardController@index'
        ],
        'panel.options' => [
            'path'   => '/options/',
            'action' => 'Formwork\Panel\Controllers\OptionsController@index'
        ],
        'panel.options.system' => [
            'path'    => '/options/system/',
            'action'  => 'Formwork\Panel\Controllers\OptionsController@systemOptions',
            'methods' => ['GET', 'POST']
        ],
        'panel.options.site' => [
            'path'    => '/options/site/',
            'action'  => 'Formwork\Panel\Controllers\OptionsController@siteOptions',
            'methods' => ['GET', 'POST']
        ],
        'panel.options.updates' => [
            'path'   => '/options/updates/',
            'action' => 'Formwork\Panel\Controllers\OptionsController@updates'
        ],
        'panel.options.info' => [
            'path'   => '/options/info/',
            'action' => 'Formwork\Panel\Controllers\OptionsController@info'
        ],
        'panel.pages' => [
            'path'   => '/pages/',
            'action' => 'Formwork\Panel\Controllers\PagesController@index'
        ],
        'panel.pages.new' => [
            'path'    => '/pages/new/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@create',
            'methods' => ['POST']
        ],
        'panel.pages.edit' => [
            'path'    => '/pages/{page}/edit/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST']
        ],
        'panel.pages.edit.lang' => [
            'path'    => '/pages/{page}/edit/language/{language}/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST']
        ],
        'panel.pages.reorder' => [
            'path'    => '/pages/reorder/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@reorder',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'panel.pages.uploadfile' => [
            'path'    => '/pages/{page}/file/upload/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@uploadFile',
            'methods' => ['POST']
        ],
        'panel.pages.deletefile' => [
            'path'    => '/pages/{page}/file/{filename}/delete/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@deleteFile',
            'methods' => ['POST']
        ],
        'panel.pages.delete' => [
            'path'    => '/pages/{page}/delete/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@delete',
            'methods' => ['POST']
        ],
        'panel.pages.delete.lang' => [
            'path'    => '/pages/{page}/delete/language/{language}/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@delete',
            'methods' => ['POST']
        ],
        'panel.updates.check' => [
            'path'    => '/updates/check/',
            'action'  => 'Formwork\Panel\Controllers\UpdatesController@check',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'panel.updates.update' => [
            'path'    => '/updates/update/',
            'action'  => 'Formwork\Panel\Controllers\UpdatesController@update',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'panel.users' => [
            'path'   => '/users/',
            'action' => 'Formwork\Panel\Controllers\UsersController@index'
        ],
        'panel.users.new' => [
            'path'    => '/users/new/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@create',
            'methods' => ['POST']
        ],
        'panel.users.delete' => [
            'path'    => '/users/{user}/delete/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@delete',
            'methods' => ['POST']
        ],
        'panel.users.profile' => [
            'path'    => '/users/{user}/profile/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@profile',
            'methods' => ['GET', 'POST']
        ],
        'panel.register' => [
            'path'    => '/register/',
            'action'  => 'Formwork\Panel\Controllers\RegisterController@register',
            'methods' => ['GET']
        ],
        'panel.errors.notfound' => [
            'path' => '/{route}/',
            'action' => 'Formwork\Panel\Controllers\ErrorsController@notFound'
        ]
    ],
    'filters' => [
        'request.validate-size' => [
            'action' => static function () {
                // Validate HTTP request Content-Length according to `post_max_size` directive
                if (HTTPRequest::contentLength() !== null) {
                    $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size'));

                    if (HTTPRequest::contentLength() > $maxSize && $maxSize > 0) {
                        $panel = Formwork::instance()->panel();
                        $panel->notify(
                            Formwork::instance()->translations()->getCurrent()->translate('panel.request.error.post-max-size'),
                            'error'
                        );
                        return new RedirectResponse($panel->uri());
                    }
                }
            },
            'methods' => ['POST']
        ],

        'request.validate-csrf' => [
            'action' => static function () {
                // Validate CSRF token
                try {
                    CSRFToken::validate();
                } catch (RuntimeException $e) {
                    CSRFToken::destroy();
                    Session::remove('FORMWORK_USERNAME');

                    $panel = Formwork::instance()->panel();
                    $panel->notify(
                        Formwork::instance()->translations()->getCurrent()->translate('panel.login.suspicious-request-detected'),
                        'warning'
                    );

                    if (HTTPRequest::isXHR()) {
                        return JSONResponse::error('Bad Request: the CSRF token is not valid', 400);
                    }

                    return new RedirectResponse($panel->uri('/login/'));
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR']
        ],

        'panel.register' => [
            'action' => static function () {
                $panel = Formwork::instance()->panel();

                // Register panel if no user exists
                if ($panel->users()->isEmpty()) {
                    if (!HTTPRequest::isLocalhost()) {
                        return new RedirectResponse(Formwork::instance()->site()->uri());
                    }

                    if ($panel->route() !== '/register/') {
                        return new RedirectResponse($panel->uri('/register/'));
                    }

                }
            },
            'methods' => ['GET', 'POST']
        ],

        'panel.redirect-to-login' => [
            'action' => static function () {
                $panel = Formwork::instance()->panel();

                // Redirect to login if no user is logged
                if (!$panel->users()->isEmpty() && !$panel->isLoggedIn() && $panel->route() !== '/login/') {
                    Session::set('FORMWORK_REDIRECT_TO', $panel->route());
                    return new RedirectResponse($panel->uri('/login/'));
                }
            }
        ]
    ]
];
