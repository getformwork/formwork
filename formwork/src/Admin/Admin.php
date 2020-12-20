<?php

namespace Formwork\Admin;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\User;
use Formwork\Admin\Users\Users;
use Formwork\Utils\JSONResponse;
use Formwork\Utils\Session;
use Formwork\Core\Formwork;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Traits\SingletonTrait;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use Formwork\Core\Page;
use Formwork\Schemes\Scheme;
use Formwork\Translations\Translation;
use Formwork\Utils\Header;
use Formwork\Utils\Log;
use Formwork\Utils\Notification;
use Formwork\Utils\Registry;
use Formwork\Utils\Str;
use RuntimeException;
use Throwable;

final class Admin
{
    use SingletonTrait;

    /**
     * Admin accounts path
     *
     * @var string
     */
    public const ACCOUNTS_PATH = ADMIN_PATH . 'accounts' . DS;

    /**
     * Admin schemes path
     *
     * @var string
     */
    public const SCHEMES_PATH = ADMIN_PATH . 'schemes' . DS;

    /**
     * Admin logs path
     *
     * @var string
     */
    public const LOGS_PATH = ADMIN_PATH . 'logs' . DS;

    /**
     * Admin views path
     *
     * @var string
     */
    public const VIEWS_PATH = ADMIN_PATH . 'views' . DS;

    /**
     * Router instance
     *
     * @var Router
     */
    protected $router;

    /**
     * All the registered users
     *
     * @var Users
     */
    protected $users;

    /**
     * Errors controller
     *
     * @var Controllers\Errors
     */
    protected $errors;

    /**
     * Create a new Admin instance
     */
    public function __construct()
    {
        $this->initializeSingleton();

        $this->router = new Router(Uri::removeQuery($this->route()));
        $this->users = Users::load();

        $this->loadTranslations();
        $this->loadErrorHandler();
    }

    /**
     * Return whether a user is logged in
     */
    public function isLoggedIn(): bool
    {
        $username = Session::get('FORMWORK_USERNAME');
        return !empty($username) && $this->users->has($username);
    }

    /**
     * Return all registered users
     */
    public function users(): Users
    {
        return $this->users;
    }

    /**
     * Return currently logged in user
     */
    public function user(): User
    {
        $username = Session::get('FORMWORK_USERNAME');
        return $this->users->get($username);
    }

    /**
     * Get current translation
     */
    public function translation(): Translation
    {
        return $this->translation;
    }

    /**
     * Run the administration panel
     */
    public function run(): void
    {
        if (HTTPRequest::method() === 'POST') {
            $this->validateContentLength();
            $this->validateCSRFToken();
        }

        if ($this->users->isEmpty()) {
            $this->registerAdmin();
        }

        if (!$this->isLoggedIn() && $this->route() !== '/login/') {
            Session::set('FORMWORK_REDIRECT_TO', $this->route());
            $this->redirect('/login/');
        }

        $this->loadRoutes();

        $this->router->dispatch();

        if (!$this->router->hasDispatched()) {
            $this->errors->notFound();
        }
    }

    /**
     * Return a URI relative to the request root
     */
    public function uri(string $route): string
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return a URI relative to the real Admin root
     */
    public function realUri(string $route): string
    {
        return HTTPRequest::root() . 'admin/' . ltrim($route, '/');
    }

    /**
     * Get the URI of the site
     */
    public function siteUri(): string
    {
        return HTTPRequest::root();
    }

    /**
     * Return panel root
     */
    public function panelRoot(): string
    {
        return Uri::normalize(Formwork::instance()->config()->get('admin.root'));
    }

    /**
     * Get the URI of the panel
     */
    public function panelUri(): string
    {
        return HTTPRequest::root() . ltrim($this->panelRoot(), '/');
    }

    /**
     * Return the URI of a page
     *
     * @param bool|string $includeLanguage
     */
    public function pageUri(Page $page, $includeLanguage = true): string
    {
        $base = $this->siteUri();
        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : $page->language();
            if ($language !== null) {
                $base .= $language . '/';
            }
        }
        return $base . ltrim($page->route(), '/');
    }

    /**
     * Return current route
     */
    public function route(): string
    {
        return '/' . Str::removeStart(HTTPRequest::uri(), $this->panelRoot());
    }

    /**
     * Redirect to a given route
     *
     * @param int $code HTTP redirect status code
     */
    public function redirect(string $route, int $code = 302): void
    {
        Header::redirect($this->uri($route), $code);
    }

    /**
     * Redirect to the site index page
     *
     * @param int $code HTTP redirect status code
     */
    public function redirectToSite(int $code = 302): void
    {
        Header::redirect($this->siteUri(), $code);
    }

    /**
     * Redirect to the administration panel
     *
     * @param int $code HTTP redirect status code
     */
    public function redirectToPanel(int $code = 302): void
    {
        $this->redirect('/', $code);
    }

    /**
     * Redirect to the referer page
     *
     * @param int    $code    HTTP redirect status code
     * @param string $default Default route if HTTP referer is not available
     */
    public function redirectToReferer(int $code = 302, string $default = '/'): void
    {
        if (HTTPRequest::validateReferer($this->uri('/')) && HTTPRequest::referer() !== Uri::current()) {
            Header::redirect(HTTPRequest::referer(), $code);
        } else {
            Header::redirect($this->uri($default), $code);
        }
    }

    /**
     * Get scheme object from template name
     */
    public function scheme(string $template): Scheme
    {
        return new Scheme(Formwork::instance()->config()->get('templates.path') . 'schemes' . DS . $template . '.yml');
    }

    /**
     * Get a Registry object by name from logs path
     */
    public function registry(string $name): Registry
    {
        return new Registry(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Get a Log object by name from logs path
     */
    public function log(string $name): Log
    {
        return new Log(Admin::LOGS_PATH . $name . '.json');
    }

    /**
     * Send a notification
     */
    public function notify(string $text, string $type = Notification::INFO): void
    {
        Notification::send($text, $type);
    }

    /**
     * Get notification from session data
     */
    public function notification(): ?array
    {
        return Notification::exists() ? Notification::get() : null;
    }

    /**
     * Get a translation
     */
    public function translate(...$arguments)
    {
        return Formwork::instance()->translations()->getCurrent()->translate(...$arguments);
    }

    /**
     * Load proper panel translation
     */
    protected function loadTranslations(): void
    {
        $languageCode = Formwork::instance()->config()->get('admin.lang');
        if ($this->isLoggedIn()) {
            $languageCode = $this->user()->language();
        }
        $path = Formwork::instance()->config()->get('translations.paths.admin');
        Formwork::instance()->translations()->loadFromPath($path);
        Formwork::instance()->translations()->setCurrent($languageCode);
    }

    /**
     * Load the panel-styled error handler
     */
    protected function loadErrorHandler(): void
    {
        $this->errors = new Controllers\Errors();
        set_exception_handler(function (Throwable $exception): void {
            $this->errors->internalServerError($exception);
            throw $exception;
        });
    }

    /**
     * Validate HTTP request Content-Length according to post_max_size directive
     * and notify if not valid
     */
    protected function validateContentLength(): void
    {
        if (HTTPRequest::contentLength() !== null) {
            $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size'));
            if (HTTPRequest::contentLength() > $maxSize && $maxSize > 0) {
                $this->notify($this->translate('admin.request.error.post-max-size'), 'error');
                $this->redirectToReferer();
            }
        }
    }

    /**
     * Validate CSRF token and redirect to login view if not valid
     */
    protected function validateCSRFToken(): void
    {
        try {
            CSRFToken::validate();
        } catch (RuntimeException $e) {
            CSRFToken::destroy();
            Session::remove('FORMWORK_USERNAME');
            $this->notify($this->translate('admin.login.suspicious-request-detected'), 'warning');
            if (HTTPRequest::isXHR()) {
                JSONResponse::error('Bad Request: the CSRF token is not valid', 400)->send();
            }
            $this->redirect('/login/');
        }
    }

    /**
     * Register administration panel if no user exists
     */
    protected function registerAdmin(): void
    {
        if (!HTTPRequest::isLocalhost()) {
            $this->redirectToSite();
        }
        if ($this->router->request() !== '/') {
            $this->redirectToPanel();
        }
        $controller = new Controllers\Register();
        $controller->register();
        exit;
    }

    /**
     * Load administration panel routes
     */
    protected function loadRoutes(): void
    {
        // Default route
        $this->router->add(
            '/',
            function (RouteParams $params): void {
                $this->redirect('/dashboard/');
            }
        );

        // Authentication
        $this->router->add(
            ['GET', 'POST'],
            '/login/',
            Controllers\Authentication::class . '@login'
        );
        $this->router->add(
            '/logout/',
            Controllers\Authentication::class . '@logout'
        );

        // Backup
        $this->router->add(
            'XHR',
            'POST',
            '/backup/make/',
            Controllers\Backup::class . '@make'
        );
        $this->router->add(
            'POST',
            '/backup/download/{backup}/',
            Controllers\Backup::class . '@download'
        );

        // Cache
        $this->router->add(
            'XHR',
            'POST',
            '/cache/clear/',
            Controllers\Cache::class . '@clear'
        );

        // Dashboard
        $this->router->add(
            '/dashboard/',
            Controllers\Dashboard::class . '@index'
        );

        // Options
        $this->router->add(
            '/options/',
            Controllers\Options::class . '@index'
        );
        $this->router->add(
            ['GET', 'POST'],
            '/options/system/',
            Controllers\Options::class . '@systemOptions'
        );
        $this->router->add(
            ['GET', 'POST'],
            '/options/site/',
            Controllers\Options::class . '@siteOptions'
        );
        $this->router->add(
            '/options/updates/',
            Controllers\Options::class . '@updates'
        );
        $this->router->add(
            '/options/info/',
            Controllers\Options::class . '@info'
        );

        // Pages
        $this->router->add(
            '/pages/',
            Controllers\Pages::class . '@index'
        );
        $this->router->add(
            'POST',
            '/pages/new/',
            Controllers\Pages::class . '@create'
        );
        $this->router->add(
            ['GET', 'POST'],
            [
                '/pages/{page}/edit/',
                '/pages/{page}/edit/language/{language}/'
            ],
            Controllers\Pages::class . '@edit'
        );
        $this->router->add(
            'XHR',
            'POST',
            '/pages/reorder/',
            Controllers\Pages::class . '@reorder'
        );
        $this->router->add(
            'POST',
            '/pages/{page}/file/upload/',
            Controllers\Pages::class . '@uploadFile'
        );
        $this->router->add(
            'POST',
            '/pages/{page}/file/{filename}/delete/',
            Controllers\Pages::class . '@deleteFile'
        );
        $this->router->add(
            'POST',
            [
                '/pages/{page}/delete/',
                '/pages/{page}/delete/language/{language}/',
            ],
            Controllers\Pages::class . '@delete'
        );

        // Updates
        $this->router->add(
            'XHR',
            'POST',
            '/updates/check/',
            Controllers\Updates::class . '@check'
        );
        $this->router->add(
            'XHR',
            'POST',
            '/updates/update/',
            Controllers\Updates::class . '@update'
        );

        // Users
        $this->router->add(
            '/users/',
            Controllers\Users::class . '@index'
        );
        $this->router->add(
            'POST',
            '/users/new/',
            Controllers\Users::class . '@create'
        );
        $this->router->add(
            'POST',
            '/users/{user}/delete/',
            Controllers\Users::class . '@delete'
        );
        $this->router->add(
            ['GET', 'POST'],
            '/users/{user}/profile/',
            Controllers\Users::class . '@profile'
        );
    }
}
