<?php

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\AuthenticationController;
use Formwork\Panel\Panel;
use Formwork\Security\CsrfToken;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;

return [
    'routes' => [
        'panel.index' => [
            'path'   => '/',
            'action' => fn(Panel $panel) => new RedirectResponse($panel->uri('/dashboard/')),
        ],

        'panel.login' => [
            'path'    => '/login/',
            'action'  => 'Formwork\Panel\Controllers\AuthenticationController@login',
            'methods' => ['GET', 'POST'],
        ],

        'panel.logout' => [
            'path'   => '/logout/',
            'action' => 'Formwork\Panel\Controllers\AuthenticationController@logout',
        ],

        'panel.assets' => [
            'path'   => '/assets/{type:alpha}/{file:all}/',
            'action' => 'Formwork\Panel\Controllers\AssetsController@asset',
        ],

        'panel.dashboard' => [
            'path'   => '/dashboard/',
            'action' => 'Formwork\Panel\Controllers\DashboardController@index',
        ],

        'panel.files.delete' => [
            'path'    => '/files/pages/{page:all}/{filename}/delete/',
            'action'  => 'Formwork\Panel\Controllers\FilesController@delete',
            'methods' => ['POST'],
        ],

        'panel.files.rename' => [
            'path'    => '/files/pages/{page:all}/{filename}/rename/',
            'action'  => 'Formwork\Panel\Controllers\FilesController@rename',
            'methods' => ['POST'],
        ],

        'panel.files.replace' => [
            'path'    => '/files/pages/{page:all}/{filename}/replace/',
            'action'  => 'Formwork\Panel\Controllers\FilesController@replace',
            'methods' => ['POST'],
        ],

        'panel.files.index' => [
            'path'    => '/files/pages/{page:all}/{filename}/',
            'action'  => 'Formwork\Panel\Controllers\FilesController@index',
            'methods' => ['GET', 'POST'],
        ],

        'panel.pages' => [
            'path'   => '/pages/',
            'action' => 'Formwork\Panel\Controllers\PagesController@index',
        ],

        'panel.pages.new' => [
            'path'    => '/pages/new/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@create',
            'methods' => ['POST'],
        ],

        'panel.pages.edit' => [
            'path'    => '/pages/{page:all}/edit/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST'],
        ],

        'panel.pages.upload' => [
            'path'    => '/pages/{page:all}/edit/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@upload',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.pages.preview' => [
            'path'    => '/pages/{page:all}/preview/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@preview',
            'methods' => ['POST'],
        ],

        'panel.pages.edit.lang' => [
            'path'    => '/pages/{page:all}/edit/language/{language:alpha}/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@edit',
            'methods' => ['GET', 'POST'],
        ],

        'panel.pages.reorder' => [
            'path'    => '/pages/reorder/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@reorder',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.pages.deleteFile' => [
            'path'    => '/pages/{page:all}/file/delete/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@deleteFile',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.pages.renameFile' => [
            'path'    => '/pages/{page:all}/file/rename/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@renameFile',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.pages.replaceFile' => [
            'path'    => '/pages/{page:all}/file/replace/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@replaceFile',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.pages.delete' => [
            'path'    => '/pages/{page:all}/delete/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@delete',
            'methods' => ['POST'],
        ],

        'panel.pages.delete.lang' => [
            'path'    => '/pages/{page:all}/delete/language/{language:alpha}/',
            'action'  => 'Formwork\Panel\Controllers\PagesController@delete',
            'methods' => ['POST'],
        ],

        'panel.updates.check' => [
            'path'    => '/updates/check/',
            'action'  => 'Formwork\Panel\Controllers\UpdatesController@check',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.updates.update' => [
            'path'    => '/updates/update/',
            'action'  => 'Formwork\Panel\Controllers\UpdatesController@update',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.statistics' => [
            'path'   => '/statistics/',
            'action' => 'Formwork\Panel\Controllers\StatisticsController@index',
        ],

        'panel.users' => [
            'path'   => '/users/',
            'action' => 'Formwork\Panel\Controllers\UsersController@index',
        ],

        'panel.users.new' => [
            'path'    => '/users/new/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@create',
            'methods' => ['POST'],
        ],

        'panel.users.profile' => [
            'path'    => '/users/{user:[a-z0-9_-]+}/profile/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@profile',
            'methods' => ['GET', 'POST'],
        ],

        'panel.users.images' => [
            'path'    => '/users/images/{image}/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@images',
            'methods' => ['GET'],
        ],

        'panel.users.delete' => [
            'path'    => '/users/{user:[a-z0-9_-]+}/delete/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@delete',
            'methods' => ['POST'],
        ],

        'panel.users.deleteImage' => [
            'path'    => '/users/{user:[a-z0-9_-]+}/image/delete/',
            'action'  => 'Formwork\Panel\Controllers\UsersController@deleteImage',
            'methods' => ['POST'],
        ],

        'panel.options' => [
            'path'   => '/options/',
            'action' => 'Formwork\Panel\Controllers\OptionsController@index',
        ],

        'panel.options.system' => [
            'path'    => '/options/system/',
            'action'  => 'Formwork\Panel\Controllers\OptionsController@systemOptions',
            'methods' => ['GET', 'POST'],
        ],

        'panel.options.site' => [
            'path'    => '/options/site/',
            'action'  => 'Formwork\Panel\Controllers\OptionsController@siteOptions',
            'methods' => ['GET', 'POST'],
        ],

        'panel.tools' => [
            'path'   => '/tools/',
            'action' => 'Formwork\Panel\Controllers\ToolsController@index',
        ],

        'panel.tools.backups' => [
            'path'   => '/tools/backups/',
            'action' => 'Formwork\Panel\Controllers\ToolsController@backups',
        ],

        'panel.tools.updates' => [
            'path'   => '/tools/updates/',
            'action' => 'Formwork\Panel\Controllers\ToolsController@updates',
        ],

        'panel.tools.info' => [
            'path'   => '/tools/info/',
            'action' => 'Formwork\Panel\Controllers\ToolsController@info',
        ],

        'panel.backup.make' => [
            'path'    => '/backup/make/',
            'action'  => 'Formwork\Panel\Controllers\BackupController@make',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.backup.download' => [
            'path'    => '/backup/download/{backup:base64}/',
            'action'  => 'Formwork\Panel\Controllers\BackupController@download',
            'methods' => ['GET', 'POST'],
        ],

        'panel.backup.delete' => [
            'path'    => '/backup/delete/{backup:base64}/',
            'action'  => 'Formwork\Panel\Controllers\BackupController@delete',
            'methods' => ['POST'],
        ],

        'panel.cache.clear' => [
            'path'    => '/cache/clear/{type:alpha}?/',
            'action'  => 'Formwork\Panel\Controllers\CacheController@clear',
            'methods' => ['POST'],
            'types'   => ['XHR'],
        ],

        'panel.register' => [
            'path'    => '/register/',
            'action'  => 'Formwork\Panel\Controllers\RegisterController@register',
            'methods' => ['GET', 'POST'],
        ],

        'panel.errors.notFound' => [
            'path'   => '/{route:all}/',
            'action' => 'Formwork\Panel\Controllers\ErrorsController@notFound',
        ],
    ],

    'filters' => [
        'panel.request.validateSize' => [
            'action' => static function (Request $request, Translations $translations, Panel $panel) {
                // Validate HTTP request Content-Length according to `post_max_size` directive
                if ($request->contentLength() !== null) {
                    $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size') ?: '0');

                    if ($request->contentLength() > $maxSize && $maxSize > 0) {
                        $panel->notify(
                            $translations->getCurrent()->translate('panel.request.error.postMaxSize'),
                            'error'
                        );
                        return new RedirectResponse($panel->uri());
                    }
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'panel.request.validateCsrf' => [
            'action' => static function (Request $request, Translations $translations, Panel $panel, CsrfToken $csrfToken) {
                $tokenName = $panel->getCsrfTokenName();
                $token = (string) $request->input()->get('csrf-token');

                if (!$csrfToken->validate($tokenName, $token)) {
                    $csrfToken->destroy($tokenName);

                    if ($panel->isLoggedIn()) {
                        $panel->user()->logout();
                    }

                    $panel->notify(
                        $translations->getCurrent()->translate('panel.login.suspiciousRequestDetected'),
                        'warning'
                    );

                    if ($request->isXmlHttpRequest()) {
                        return JsonResponse::error('Bad Request: the CSRF token is not valid', ResponseStatus::BadRequest);
                    }

                    return new RedirectResponse($panel->uri('/login/'));
                }
            },
            'methods' => ['POST'],
            'types'   => ['HTTP', 'XHR'],
        ],

        'panel.checkAssets' => [
            'action' => static function (Config $config, ViewFactory $viewFactory) {
                $path = $config->get('system.panel.paths.assets');
                $assets = ['css/panel.min.css', 'js/app.min.js'];

                foreach ($assets as $asset) {
                    $assetPath = FileSystem::joinPaths($path, $asset);
                    if (!FileSystem::isFile($assetPath, assertExists: false)) {
                        $view = $viewFactory->make('errors.panel.assets');
                        return new Response($view->render(), ResponseStatus::InternalServerError);
                    }
                }
            },
        ],

        'panel.register' => [
            'action' => static function (Request $request, Site $site, Panel $panel) {
                // Register panel if no user exists
                if ($site->users()->isEmpty()) {
                    if (!$request->isLocalhost()) {
                        return new RedirectResponse($site->uri());
                    }

                    if ($panel->route() !== '/register/' && !Str::startsWith($panel->route(), '/assets/')) {
                        return new RedirectResponse($panel->uri('/register/'));
                    }
                }
            },
            'methods' => ['GET', 'POST'],
        ],

        'panel.redirectToLogin' => [
            'action' => static function (Request $request, Site $site, Panel $panel) {
                // Redirect to login if no user is logged
                if (
                    !$site->users()->isEmpty() && !$panel->isLoggedIn()
                    && !in_array($panel->route(), ['/login/', '/logout/'], true)
                    && !Str::startsWith($panel->route(), '/assets/')
                ) {
                    $request->session()->set(AuthenticationController::SESSION_REDIRECT_KEY, $panel->route());
                    return new RedirectResponse($panel->uri('/login/'));
                }
            },
        ],
    ],
];
