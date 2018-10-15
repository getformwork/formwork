<?php

namespace Formwork\Admin;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\Users;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Admin\Utils\Session;
use Formwork\Core\Formwork;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use LogicException;
use RuntimeException;

class Admin
{
    use AdminTrait;

    public static $instance;

    protected $router;

    protected $users;

    protected $language;

    protected $errors;

    public function __construct()
    {
        if (!is_null(static::$instance)) {
            throw new LogicException('Admin class already instantiated');
        }
        static::$instance = $this;

        if (!Formwork::instance()->option('admin.enabled')) {
            $this->redirectToSite();
        }

        $this->router = new Router(Uri::removeQuery(HTTPRequest::uri()));
        $this->users = Users::load();

        $this->loadLanguages();
        $this->loadErrorHandler();
    }

    public static function instance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    public function isLoggedIn()
    {
        $username = Session::get('FORMWORK_USERNAME');
        return !empty($username) && $this->users->has($username);
    }

    public function user()
    {
        $username = Session::get('FORMWORK_USERNAME');
        return $this->users->get($username);
    }

    public function run()
    {
        if (HTTPRequest::method() === 'POST') {
            $this->validateContentLength();
            $this->validateCSRFToken();
        }

        if ($this->users->isEmpty()) {
            $this->registerAdmin();
        }

        if (!$this->isLoggedIn() && HTTPRequest::uri() !== '/login/') {
            Session::set('FORMWORK_REDIRECT_TO', HTTPRequest::uri());
            $this->redirect('/login/');
        }

        $this->loadRoutes();

        $this->router->dispatch();

        if (!$this->router->hasDispatched()) {
            $this->errors->notFound();
        }
    }

    protected function loadLanguages()
    {
        $languageCode = Formwork::instance()->option('admin.lang');
        if ($this->isLoggedIn()) {
            $languageCode = $this->user()->get('language', $languageCode);
        }
        $this->language = Language::load($languageCode);
    }

    protected function loadErrorHandler()
    {
        $this->errors = new Controllers\Errors();
        set_exception_handler(function ($exception) {
            $this->errors->internalServerError();
            throw $exception;
        });
    }

    protected function validateContentLength()
    {
        if (!is_null(HTTPRequest::contentLength())) {
            $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size'));
            if (HTTPRequest::contentLength() > $maxSize && $maxSize > 0) {
                $this->notify($this->label('request.error.post-max-size'), 'error');
                $this->redirectToReferer();
            }
        }
    }

    protected function validateCSRFToken()
    {
        try {
            CSRFToken::validate();
        } catch (RuntimeException $e) {
            CSRFToken::destroy();
            Session::remove('FORMWORK_USERNAME');
            $this->notify($this->label('login.suspicious-request-detected'), 'warning');
            if (HTTPRequest::isXHR()) {
                JSONResponse::error('Bad Request: the CSRF token is not valid', 400)->send();
            }
            $this->redirect('/login/');
        }
    }

    protected function registerAdmin()
    {
        if ($this->router->request() !== '/') {
            $this->redirectToPanel();
        }
        $controller = new Controllers\Register();
        $controller->register();
        exit;
    }

    protected function loadRoutes()
    {
        // Default route
        $this->router->add(
            '/',
            function (RouteParams $params) {
                $this->redirect('/dashboard/');
            }
        );

        // Authentication
        $this->router->add(
            array('GET', 'POST'),
            '/login/',
            array(new Controllers\Authentication(), 'login')
        );
        $this->router->add(
            '/logout/',
            array(new Controllers\Authentication(), 'logout')
        );

        // Backup
        $this->router->add(
            'XHR',
            'POST',
            '/backup/make/',
            array(new Controllers\Backup(), 'make')
        );
        $this->router->add(
            'POST',
            '/backup/download/{backup}/',
            array(new Controllers\Backup(), 'download')
        );

        // Cache
        $this->router->add(
            'XHR',
            'POST',
            '/cache/clear/',
            array(new Controllers\Cache(), 'clear')
        );

        // Dashboard
        $this->router->add(
            '/dashboard/',
            array(new Controllers\Dashboard(), 'index')
        );

        // Options
        $this->router->add(
            '/options/',
            array(new Controllers\Options(), 'index')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/options/system/',
            array(new Controllers\Options(), 'systemOptions')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/options/site/',
            array(new Controllers\Options(), 'siteOptions')
        );
        $this->router->add(
            '/options/updates/',
            array(new Controllers\Options(), 'updates')
        );
        $this->router->add(
            '/options/info/',
            array(new Controllers\Options(), 'info')
        );

        // Pages
        $this->router->add(
            '/pages/',
            array(new Controllers\Pages(), 'index')
        );
        $this->router->add(
            'POST',
            '/pages/new/',
            array(new Controllers\Pages(), 'create')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/pages/{page}/edit/',
            array(new Controllers\Pages(), 'edit')
        );
        $this->router->add(
            'XHR',
            'POST',
            '/pages/reorder/',
            array(new Controllers\Pages(), 'reorder')
        );
        $this->router->add(
            'POST',
            '/pages/{page}/file/upload/',
            array(new Controllers\Pages(), 'uploadFile')
        );
        $this->router->add(
            'POST',
            '/pages/{page}/file/{filename}/delete/',
            array(new Controllers\Pages(), 'deleteFile')
        );
        $this->router->add(
            'POST',
            '/pages/{page}/delete/',
            array(new Controllers\Pages(), 'delete')
        );

        // Updates
        $this->router->add(
            'XHR',
            'POST',
            '/updates/check/',
            array(new Controllers\Updates(), 'check')
        );
        $this->router->add(
            'XHR',
            'POST',
            '/updates/update/',
            array(new Controllers\Updates(), 'update')
        );

        // Users
        $this->router->add(
            '/users/',
            array(new Controllers\Users(), 'index')
        );
        $this->router->add(
            'POST',
            '/users/new/',
            array(new Controllers\Users(), 'create')
        );
        $this->router->add(
            'POST',
            '/users/{user}/delete/',
            array(new Controllers\Users(), 'delete')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/users/{user}/profile/',
            array(new Controllers\Users(), 'profile')
        );
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if (method_exists(AdminTrait::class, $name)) {
            return $this->$name(...$arguments);
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
