<?php

namespace Formwork\Panel;

use Formwork\Assets;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Http\Session\MessageType;
use Formwork\Languages\LanguageCodes;
use Formwork\Users\ColorScheme;
use Formwork\Users\Exceptions\UserNotLoggedException;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

final class Panel
{
    protected const CSRF_TOKEN_NAME = 'panel';

    /**
     * Assets instance
     */
    protected Assets $assets;

    /**
     * Create a new Panel instance
     */
    public function __construct(
        protected Config $config,
        protected Request $request,
        protected Users $users
    ) {
    }

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
     */
    public function user(): User
    {
        return $this->users->loggedIn()
            ?? throw new UserNotLoggedException('No user is logged in');
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
     *
     * @return list<array{text: string, type: string, interval: int, icon: string}>
     */
    public function notifications(): array
    {
        $messages = $this->request->session()->messages()->getAll() ?: null;

        if ($messages === null) {
            return [];
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
        return $this->assets ?? ($this->assets = new Assets($this->config->get('system.panel.paths.assets'), $this->realUri('/assets/')));
    }

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
}
