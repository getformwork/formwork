<?php

namespace Formwork\Admin;

use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Admin\Utils\Language;
use Formwork\Admin\Utils\Log;
use Formwork\Admin\Utils\Notification;
use Formwork\Admin\Utils\Registry;
use Formwork\Admin\Utils\Session;
use Formwork\Core\Formwork;
use Formwork\Parsers\YAML;
use Formwork\Router\Router;
use Formwork\Router\RouteParams;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;
use LogicException;
use RuntimeException;

class Admin
{
    public static $instance;

    protected static $languages;

    protected $router;

    protected $users;

    public function __construct()
    {
        if (!is_null(static::$instance)) {
            throw new LogicException('Admin class already instantiated');
        }
        static::$instance = $this;

        if (!Formwork::instance()->option('admin.enabled')) {
            Header::redirect(rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/');
        }

        $this->router = new Router(Uri::removeQuery(HTTPRequest::uri()));
        $this->users = Users::load();

        $languageFile = LANGUAGES_PATH . $this->language() . '.yml';

        if (!FileSystem::exists($languageFile)) {
            throw new RuntimeException('Cannot load admin language file');
        }

        Language::load($this->language(), YAML::parseFile($languageFile));
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
        if (!is_null(static::$languages)) {
            return static::$languages;
        }
        foreach (FileSystem::listFiles(LANGUAGES_PATH) as $file) {
            $data = YAML::parseFile(LANGUAGES_PATH . $file);
            $code = FileSystem::name($file);
            static::$languages[$code] = $data['language.name'] . ' (' . $code . ')';
        }
        return static::$languages;
    }

    public function language()
    {
        return Formwork::instance()->option('admin.lang');
    }

    public function loggedUser()
    {
        $username = Session::get('FORMWORK_USERNAME');
        return $this->users->get($username);
    }

    public function isLoggedIn()
    {
        return !is_null($user = Session::get('FORMWORK_USERNAME')) && $this->users->has($user);
    }

    public function ensureLogin()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login/', 302, true);
        }
    }

    public function uri($subpath)
    {
        return HTTPRequest::root() . ltrim($subpath, '/');
    }

    public function redirect($uri, $code = 302, $exit = false)
    {
        Header::redirect($this->uri($uri), $code, $exit);
    }

    public function registry($name)
    {
        return new Registry(LOGS_PATH . $name . '.json');
    }

    public function log($name)
    {
        return new Log(LOGS_PATH . $name . '.json');
    }

    public function run()
    {
        if (HTTPRequest::method() == 'POST') {
            try {
                CSRFToken::validate();
            } catch (RuntimeException $e) {
                CSRFToken::destroy();
                Session::remove('FORMWORK_USERNAME');
                Notification::send(Language::get('login.suspicious-request-detected'), 'warning');
                if (HTTPRequest::isXHR()) {
                    JSONResponse::error('Not authorized!', 403)->send();
                }
                $this->redirect('/login/', 302, true);
            }
        }

        if ($this->users->empty()) {
            if ($this->router->request() != '/') {
                $this->redirect('/', 302, true);
            }
            $controller = new Controllers\Register();
            return $controller->register();
        }

        $this->router->add(
            '/',
            function (RouteParams $params) {
                $this->ensureLogin();
                $this->redirect('/dashboard/', 302, true);
            }
        );

        $this->router->add(
            array('GET', 'POST'),
            '/login/',
            array(new Controllers\Authentication(), 'login')
        );
        $this->router->add(
            '/logout/',
            array(new Controllers\Authentication(), 'logout')
        );

        $this->router->add(
            array('GET', 'POST'),
            '/dashboard/',
            array(new Controllers\Dashboard(), 'run')
        );

        $this->router->add(
            '/pages/',
            array(new Controllers\Pages(), 'list')
        );
        $this->router->add(
            'POST',
            '/pages/new/',
            array(new Controllers\Pages(), 'new')
        );
        $this->router->add(
            array('GET', 'POST'),
            '/pages/{page}/edit/',
            array(new Controllers\Pages(), 'edit')
        );
        $this->router->add(
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

        $this->router->add(
            '/options/',
            array(new Controllers\Options(), 'run')
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
            '/options/info/',
            array(new Controllers\Options(), 'info')
        );

        $this->router->add(array(
            '/users/'
        ), array(new Controllers\Users(), 'run'));
        $this->router->add(
            'POST',
            '/users/new/',
            array(new Controllers\Users(), 'new')
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

        $this->router->add(
            array('GET', 'POST'),
            '/cache/clear/',
            array(new Controllers\Cache(), 'clear')
        );

        $this->router->add(
            'POST',
            '/updates/check/',
            array(new Controllers\Updates(), 'check')
        );
        $this->router->add(
            'POST',
            '/updates/update/',
            array(new Controllers\Updates(), 'update')
        );

        $this->router->dispatch();

        if (!$this->router->hasDispatched()) {
            Header::notFound();
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
