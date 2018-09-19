<?php

namespace Formwork\Admin;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\Users;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Admin\Utils\Language;
use Formwork\Admin\Utils\Session;
use Formwork\Core\Formwork;
use Formwork\Parsers\YAML;
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

    protected static $languages;

    protected $router;

    protected $users;

    protected $errors;

    public function __construct()
    {
        if (!is_null(static::$instance)) {
            throw new LogicException('Admin class already instantiated');
        }
        static::$instance = $this;

        $this->errors = new Controllers\Errors();

        if (!Formwork::instance()->option('admin.enabled')) {
            $this->redirectToSite(302, true);
        }

        $this->router = new Router(Uri::removeQuery(HTTPRequest::uri()));
        $this->users = Users::load();

        $this->loadLanguages();
        $languageFile = LANGUAGES_PATH . $this->language() . '.yml';

        if (!FileSystem::isReadable($languageFile)) {
            throw new RuntimeException('Cannot load Admin language file');
        }
        Language::load($this->language(), YAML::parseFile($languageFile));

        set_exception_handler(function ($exception) {
            $this->errors->internalServerError();
            throw $exception;
        });
    }

    public static function instance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    public static function languages()
    {
        return static::$languages;
    }

    public function isLoggedIn()
    {
        return !is_null($user = Session::get('FORMWORK_USERNAME')) && $this->users->has($user);
    }

    public function loggedUser()
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
            $this->redirect('/login/', 302, true);
        }

        $this->loadRoutes();

        $this->router->dispatch();

        if (!$this->router->hasDispatched()) {
            $this->errors->notFound();
        }
    }

    protected function validateContentLength()
    {
        if (!is_null(HTTPRequest::contentLength())) {
            $maxSize = FileSystem::shorthandToBytes(ini_get('post_max_size'));
            if (HTTPRequest::contentLength() > $maxSize && $maxSize > 0) {
                $this->notify($this->label('request.error.post-max-size'), 'error');
                $this->redirectToReferer(302, true);
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
                JSONResponse::error('Not authorized!', 403)->send();
            }
            $this->redirect('/login/', 302, true);
        }
    }

    protected function registerAdmin()
    {
        if ($this->router->request() !== '/') {
            $this->redirectToPanel(302, true);
        }
        $controller = new Controllers\Register();
        return $controller->register();
    }

    protected function loadRoutes()
    {
        // Default route
        $this->router->add(
            '/',
            function (RouteParams $params) {
                $this->redirect('/dashboard/', 302, true);
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
            array(new Controllers\Options(), 'system')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/options/site/',
            array(new Controllers\Options(), 'site')
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

    protected function loadLanguages()
    {
        foreach (FileSystem::listFiles(LANGUAGES_PATH) as $file) {
            $code = FileSystem::name($file);
            static::$languages[$code] = Language::codeToNativeName($code) . ' (' . $code . ')';
        }
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new LogicException('Invalid method');
    }
}
