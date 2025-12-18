<?php

namespace Formwork\Panel;

use Formwork\Assets\Assets;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Http\Session\MessageType;
use Formwork\Languages\LanguageCodes;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Navigation\NavigationItem;
use Formwork\Panel\Navigation\NavigationItemCollection;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Users\ColorScheme;
use Formwork\Users\Exceptions\UserNotLoggedException;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

final class Panel
{
    private const string CSRF_TOKEN_NAME = 'panel';

    /**
     * Panel navigation items
     */
    private NavigationItemCollection $navigation;

    /**
     * Assets instance
     */
    private Assets $assets;

    public function __construct(
        private readonly Container $container,
        private Config $config,
        private Request $request,
        private Users $users,
        private Modals $modals,
        private Translations $translations,
    ) {}

    /**
     * Return whether a user is logged in
     */
    public function isLoggedIn(): bool
    {
        if (!$this->request->hasPreviousSession()) {
            return false;
        }
        return $this->users->loggedIn() !== null;
    }

    /**
     * Return currently logged in user
     *
     * @throws UserNotLoggedException If no user is logged in
     */
    public function user(): User
    {
        return $this->users->loggedIn()
            ?? throw new UserNotLoggedException('No user is logged in');
    }

    /**
     * Return the path to the panel
     */
    public function path(): string
    {
        return $this->config->get('system.panel.path');
    }

    /**
     * Return a URI relative to the request root
     */
    public function uri(string $route = ''): string
    {
        return $this->panelUri() . ltrim($route, '/');
    }

    /**
     * Return panel root
     */
    public function panelRoot(): string
    {
        return Uri::normalize(Str::append($this->config->get('system.panel.root'), '/'));
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
     * Get the panel navigation
     */
    public function navigation(): NavigationItemCollection
    {
        $translation = $this->translations->getCurrent();
        if (!isset($this->navigation)) {
            $items = $this->container->call(require $this->config->get('system.panel.config.navigation'), [
                'translation' => $translation,
            ]);
            $this->navigation = new NavigationItemCollection();
            $this->navigation->setMultiple(Arr::map($items, fn(array $data, string $id) => new NavigationItem($id, $data)));
        }
        return $this->navigation;
    }

    /**
     * Send a notification
     */
    public function notify(string $text, string|MessageType $type = MessageType::Info): void
    {
        $this->request->session()->messages()->set(is_string($type) ? MessageType::from($type) : $type, $text);
    }

    /**
     * Get notification from session data
     *
     * @return list<array{text: string, type: string, interval: int}>
     */
    public function notifications(): array
    {
        $messages = $this->request->session()->messages()->getAll() ?: null;

        if ($messages === null) {
            return [];
        }

        $interval = 5000;

        $notifications = [];

        foreach ($messages as $type => $message) {
            foreach ($message as $text) {
                $notifications[] = [
                    'text'     => $text,
                    'type'     => $type,
                    'interval' => $interval,
                ];
            }
        }

        return $notifications;
    }

    /**
     * Get Modals instance
     */
    public function modals(): Modals
    {
        return $this->modals;
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        return $this->assets ??= new Assets($this->config->get('system.panel.paths.assets'), $this->uri('/assets/'));
    }

    /**
     * Get the panel color scheme
     *
     * If the user is logged in and has set a color scheme, it will be used.
     * Otherwise, the default color scheme from the configuration will be used.
     */
    public function colorScheme(): ColorScheme
    {
        $colorScheme = ColorScheme::from($this->config->get('system.panel.colorScheme'));
        if ($this->isLoggedIn()) {
            if ($this->user()->colorScheme() === ColorScheme::Auto) {
                return ColorScheme::from($this->request->cookies()->get('formwork_preferred_color_scheme', $colorScheme->value));
            }
            return $this->user()->colorScheme();
        }
        return $colorScheme;
    }

    /**
     * Available translations helper
     *
     * @return array<string, string>
     */
    public function availableTranslations(): array
    {
        /**
         * @var array<string, string> $translations
         */
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

    /**
     * Get panel CSRF token name
     */
    public function getCsrfTokenName(): string
    {
        return self::CSRF_TOKEN_NAME;
    }

    /**
     * Get javascript app config
     *
     * @return array<string, mixed>
     */
    public function getAppConfig(): array
    {
        return $this->container->call(require $this->config->get('system.panel.config.app'), [
            'translation' => $this->translations->getCurrent(),
        ]);
    }
}
