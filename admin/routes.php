<?php

return [
    'routes' => [
        'admin.index' => [
            'path'   => '/',
            'action' => fn () => \Formwork\Formwork::instance()->admin()->redirect('/dashboard/')
        ],
        'admin.login' => [
            'path'    => '/login/',
            'action'  => 'Formwork\\Admin\\Controllers\\AuthenticationController@login',
            'methods' => ['GET', 'POST']
        ],
        'admin.logout' => [
            'path'   => '/logout/',
            'action' => 'Formwork\\Admin\\Controllers\\AuthenticationController@logout'
        ],
        'admin.backup.make' => [
            'path'    => '/backup/make/',
            'action'  => 'Formwork\\Admin\\Controllers\\BackupController@make',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'admin.backup.download' => [
            'path'    => '/backup/download/{backup}/',
            'action'  => 'Formwork\\Admin\\Controllers\\BackupController@download',
            'methods' => ['POST']
        ],
        'admin.cache.clear' => [
            'path'    => '/cache/clear/',
            'action'  => 'Formwork\\Admin\\Controllers\\CacheController@clear',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'admin.dashboard' => [
            'path'   => '/dashboard/',
            'action' => '\Formwork\Admin\Controllers\DashboardController@index'
        ],
        'admin.options' => [
            'path'   => '/options/',
            'action' => '\Formwork\Admin\Controllers\OptionsController@index'
        ],
        'admin.options.system' => [
            'path'    => '/options/system/',
            'action'  => '\Formwork\Admin\Controllers\OptionsController@systemOptions',
            'methods' => ['GET', 'POST']
        ],
        'admin.options.site' => [
            'path'    => '/options/site/',
            'action'  => '\Formwork\Admin\Controllers\OptionsController@siteOptions',
            'methods' => ['GET', 'POST']
        ],
        'admin.options.updates' => [
            'path'   => '/options/updates/',
            'action' => '\Formwork\Admin\Controllers\OptionsController@updates'
        ],
        'admin.options.info' => [
            'path'   => '/options/info/',
            'action' => '\Formwork\Admin\Controllers\OptionsController@info'
        ],
        'admin.pages' => [
            'path'   => '/pages/',
            'action' => '\Formwork\Admin\Controllers\PagesController@index'
        ],
        'admin.pages.new' => [
            'path'    => '/pages/new/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@create',
            'methods' => ['POST']
        ],
        'admin.pages.edit' => [
            'path'    => '/pages/{page}/edit/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST']
        ],
        'admin.pages.edit.lang' => [
            'path'    => '/pages/{page}/edit/language/{language}/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST']
        ],
        'admin.pages.reorder' => [
            'path'    => '/pages/reorder/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@reorder',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'admin.pages.uploadfile' => [
            'path'    => '/pages/{page}/file/upload/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@uploadFile',
            'methods' => ['POST']
        ],
        'admin.pages.deletefile' => [
            'path'    => '/pages/{page}/file/{filename}/delete/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@deleteFile',
            'methods' => ['POST']
        ],
        'admin.pages.delete' => [
            'path'    => '/pages/{page}/delete/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@delete',
            'methods' => ['POST']
        ],
        'admin.pages.delete.lang' => [
            'path'    => '/pages/{page}/delete/language/{language}/',
            'action'  => '\Formwork\Admin\Controllers\PagesController@delete',
            'methods' => ['POST']
        ],
        'admin.updates.check' => [
            'path'    => '/updates/check/',
            'action'  => '\Formwork\Admin\Controllers\UpdatesController@check',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'admin.updates.update' => [
            'path'    => '/updates/update/',
            'action'  => '\Formwork\Admin\Controllers\UpdatesController@update',
            'methods' => ['POST'],
            'types'   => ['XHR']
        ],
        'admin.users' => [
            'path'   => '/users/',
            'action' => '\Formwork\Admin\Controllers\UsersController@index'
        ],
        'admin.users.new' => [
            'path'    => '/users/new/',
            'action'  => '\Formwork\Admin\Controllers\UsersController@create',
            'methods' => ['POST']
        ],
        'admin.users.delete' => [
            'path'    => '/users/{user}/delete/',
            'action'  => '\Formwork\Admin\Controllers\UsersController@delete',
            'methods' => ['POST']
        ],
        'admin.users.profile' => [
            'path'    => '/users/{user}/profile/',
            'action'  => '\Formwork\Admin\Controllers\UsersController@profile',
            'methods' => ['GET', 'POST']
        ]
    ],
    'filters' => [
        'request.validate-size' => [
            'action' => static function () {
                // Validate HTTP request Content-Length according to post_max_size directive
                if (\Formwork\Utils\HTTPRequest::contentLength() !== null) {
                    $maxSize = \Formwork\Utils\FileSystem::shorthandToBytes(ini_get('post_max_size'));
                    if (\Formwork\Utils\HTTPRequest::contentLength() > $maxSize && $maxSize > 0) {
                        $admin = \Formwork\Formwork::instance()->admin();
                        $admin->notify($admin->translate('admin.request.error.post-max-size'), 'error');
                        return $admin->redirectToReferer();
                    }
                }
            },
            'methods' => ['POST']
        ],
        'request.validate-csrf' => [
            'action' => static function () {
                // Validate CSRF token
                try {
                    \Formwork\Admin\Security\CSRFToken::validate();
                } catch (RuntimeException $e) {
                    \Formwork\Admin\Security\CSRFToken::destroy();
                    \Formwork\Utils\Session::remove('FORMWORK_USERNAME');
                    $admin = \Formwork\Formwork::instance()->admin();
                    $admin->notify($admin->translate('admin.login.suspicious-request-detected'), 'warning');
                    if (\Formwork\Utils\HTTPRequest::isXHR()) {
                        return \Formwork\Response\JSONResponse::error('Bad Request: the CSRF token is not valid', 400);
                    }
                    return $admin->redirect('/login/');
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR']
        ],
        'admin.register' => [
            'action' => static function () {
                $admin = \Formwork\Formwork::instance()->admin();
                // Register admin if no user exists
                if ($admin->users()->isEmpty()) {
                    if (!\Formwork\Utils\HTTPRequest::isLocalhost()) {
                        return $admin->redirectToSite();
                    }
                    if ($admin->route() !== '/') {
                        return $admin->redirectToPanel();
                    }
                    $controller = new \Formwork\Admin\Controllers\RegisterController();
                    return $controller->register();
                }
            },
            'methods' => ['GET', 'POST']
        ],
        'admin.redirect-to-login' => [
            'action' => static function () {
                $admin = \Formwork\Formwork::instance()->admin();
                // Redirect to login if no user is logged
                if (!$admin->isLoggedIn() && $admin->route() !== '/login/') {
                    \Formwork\Utils\Session::set('FORMWORK_REDIRECT_TO', $admin->route());
                    return $admin->redirect('/login/');
                }
            }
        ]
    ]
];
