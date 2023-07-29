<?php

namespace Formwork\Panel;

use Formwork\App;
use Formwork\Assets;
use Formwork\Config;
use Formwork\Http\Request;
use Formwork\Http\Session\MessageType;
use Formwork\Languages\LanguageCodes;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Users\User;
use Formwork\Panel\Users\UserCollection;
use Formwork\Panel\Users\UserFactory;
use Formwork\Parsers\Yaml;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use Throwable;

final class Panel
{
    /**
     * All the registered users
     */
    protected UserCollection $users;

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
    public function __construct(
        protected Container $container,
        protected App $app,
        protected Config $config,
        protected Request $request,
        protected Translations $translations,
        protected UserFactory $userFactory
    ) {
    }

    public function load(): void
    {

        // TODO: Move to service loader
        $this->loadSchemes();
        $this->loadUsers();

        $this->loadTranslations();

        if ($this->isLoggedIn()) {
            $this->loadErrorHandler();
        }
    }

    /**
     * Return whether a user is logged in
     */
    public function isLoggedIn(): bool
    {
        if (!$this->request->hasPreviousSession()) {
            return false;
        }
        $username = $this->request->session()->get('FORMWORK_USERNAME');
        return !empty($username) && $this->users->has($username);
    }

    /**
     * Return all registered users
     */
    public function users(): UserCollection
    {
        return $this->users;
    }

    /**
     * Return currently logged in user
     */
    public function user(): User
    {
        $username = $this->request->session()->get('FORMWORK_USERNAME');
        return $this->users->get($username);
    }

    /**
     * Return a URI relative to the request root
     */
    public function uri(string $route = ''): string
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return a URI relative to the real Panel root
     */
    public function realUri(string $route): string
    {
        return $this->request->root() . 'panel/' . ltrim($route, '/');
    }

    /**
     * Return panel root
     */
    public function panelRoot(): string
    {
        return Uri::normalize($this->config->get('system.panel.root'));
    }

    /**
     * Get the URI of the panel
     */
    public function panelUri(): string
    {
        return $this->request->root() . ltrim($this->panelRoot(), '/');
    }

    /**
     * Return current route
     */
    public function route(): string
    {
        return '/' . Str::removeStart($this->request->uri(), $this->panelRoot());
    }

    /**
     * Send a notification
     */
    public function notify(string $text, string | MessageType $type = MessageType::Info): void
    {
        $this->request->session()->messages()->set(is_string($type) ? MessageType::from($type) : $type, $text);
    }

    /**
     * Get notification from session data
     */
    public function notification(): ?array
    {
        $messages = $this->request->session()->messages()->getAll() ?: null;

        if ($messages === null) {
            return null;
        }

        $icons = [
            'info'    => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error'   => 'exclamation-octagon',
        ];

        $interval = 5000;

        $notifications = [];

        foreach ($messages as $type => $msg) {
            foreach ($msg as $text) {
                $icon = $icons[$type];
                $notifications[] = compact('text', 'type', 'interval', 'icon');
            }
        }

        return $notifications;
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if (isset($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets($this->config->get('system.panel.paths.assets'), $this->realUri('/assets/'));
    }

    /**
     * Available translations helper
     */
    public function availableTranslations(): array
    {
        static $translations = [];

        if (!empty($translations)) {
            return $translations;
        }

        $path = $this->config->get('system.translations.paths.panel');

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'yaml') {
                $code = FileSystem::name($file);
                $translations[$code] = LanguageCodes::codeToNativeName($code) . ' (' . $code . ')';
            }
        }

        ksort($translations);

        return $translations;
    }

    protected function loadUsers(): void
    {
        $roles = [];
        foreach (FileSystem::listFiles($path = $this->config->get('system.panel.paths.roles')) as $file) {
            $parsedData = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $role = FileSystem::name($file);
            $roles[$role] = $parsedData;
        }

        $users = [];
        foreach (FileSystem::listFiles($path = $this->config->get('system.panel.paths.accounts')) as $file) {
            $parsedData = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $users[$parsedData['username']] = $this->userFactory->make($parsedData, $roles[$parsedData['role']]['permissions']);
        }

        $this->users = new UserCollection($users, $roles);
    }

    /**
     * Load proper panel translation
     */
    protected function loadTranslations(): void
    {
        $path = $this->config->get('system.translations.paths.panel');
        $this->translations->loadFromPath($path);

        if ($this->isLoggedIn()) {
            $this->translations->setCurrent($this->user()->language());
        } else {
            $this->translations->setCurrent($this->config->get('system.panel.translation'));
        }
    }

    protected function loadSchemes(): void
    {
        $path = $this->config->get('system.schemes.paths.panel');
        $this->app->schemes()->loadFromPath($path);
    }

    /**
     * Load the panel-styled error handler
     */
    protected function loadErrorHandler(): void
    {
        if ($this->config->get('system.errors.setHandlers')) {
            $this->errors = $this->container->build(Controllers\ErrorsController::class);
            set_exception_handler(function (Throwable $exception): void {
                $this->errors->internalServerError($exception)->send();
                throw $exception;
            });
        }
    }
}
