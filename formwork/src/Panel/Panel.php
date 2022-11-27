<?php

namespace Formwork\Panel;

use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Users\User;
use Formwork\Panel\Users\Users;
use Formwork\Assets;
use Formwork\Formwork;
use Formwork\Languages\LanguageCodes;
use Formwork\Pages\Page;
use Formwork\Response\RedirectResponse;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Notification;
use Formwork\Utils\Session;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use Throwable;

final class Panel
{
    /**
     * All the registered users
     */
    protected Users $users;

    /**
     * Errors controller
     */
    protected ErrorsController $errors;

    /**
     * Assets instance
     */
    protected Assets $assets;

    /**
     * Create a new Panel instance
     */
    public function __construct()
    {
        $this->load();
    }

    public function load(): void
    {
        $this->loadSchemes();
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
     * Return a URI relative to the request root
     */
    public function uri(string $route): string
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return a URI relative to the real Panel root
     */
    public function realUri(string $route): string
    {
        return HTTPRequest::root() . 'panel/' . ltrim($route, '/');
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
        return Uri::normalize(Formwork::instance()->config()->get('panel.root'));
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
    public function redirect(string $route, int $code = 302): RedirectResponse
    {
        return new RedirectResponse($this->uri($route), $code);
    }

    /**
     * Redirect to the site index page
     *
     * @param int $code HTTP redirect status code
     */
    public function redirectToSite(int $code = 302): RedirectResponse
    {
        return new RedirectResponse($this->siteUri(), $code);
    }

    /**
     * Redirect to the administration panel
     *
     * @param int $code HTTP redirect status code
     */
    public function redirectToPanel(int $code = 302): RedirectResponse
    {
        return $this->redirect('/', $code);
    }

    /**
     * Redirect to the referer page
     *
     * @param int    $code    HTTP redirect status code
     * @param string $default Default route if HTTP referer is not available
     */
    public function redirectToReferer(int $code = 302, string $default = '/'): RedirectResponse
    {
        if (HTTPRequest::validateReferer($this->uri('/')) && HTTPRequest::referer() !== Uri::current()) {
            return new RedirectResponse(HTTPRequest::referer(), $code);
        }
        return new RedirectResponse($this->uri($default), $code);
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
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if (isset($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets(PANEL_PATH . 'assets' . DS, Formwork::instance()->panel()->realUri('/assets/'));
    }

    /**
     * Available translations helper
     */
    public static function availableTranslations(): array
    {
        static $translations = [];

        if (!empty($translations)) {
            return $translations;
        }

        $path = Formwork::instance()->config()->get('translations.paths.panel');

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'yml') {
                $code = FileSystem::name($file);
                $translations[$code] = LanguageCodes::codeToNativeName($code) . ' (' . $code . ')';
            }
        }

        ksort($translations);

        return $translations;
    }

    /**
     * Load proper panel translation
     */
    protected function loadTranslations(): void
    {
        $path = Formwork::instance()->config()->get('translations.paths.panel');
        Formwork::instance()->translations()->loadFromPath($path);

        if ($this->isLoggedIn()) {
            Formwork::instance()->translations()->setCurrent($this->user()->language());
        }
    }

    protected function loadSchemes(): void
    {
        $path = Formwork::instance()->config()->get('schemes.paths.panel');
        Formwork::instance()->schemes()->loadFromPath('panel', $path);
    }

    /**
     * Load the panel-styled error handler
     */
    protected function loadErrorHandler(): void
    {
        if (Formwork::instance()->config()->get('errors.set_handlers')) {
            $this->errors = new Controllers\ErrorsController();
            set_exception_handler(function (Throwable $exception): void {
                $this->errors->internalServerError($exception)->send();
                throw $exception;
            });
        }
    }
}
