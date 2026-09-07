<?php

namespace Formwork\Panel;

use Formwork\Assets\Assets;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Http\Session\MessageType;
use Formwork\Languages\LanguageCodes;
use Formwork\Panel\Events\PanelNavigationLoadedEvent;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Navigation\NavigationItemCollection;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Users\ColorScheme;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use UnexpectedValueException;

final class Panel
{
    private const string CSRF_TOKEN_NAME = 'panel';

    /**
     * Panel navigation items
     */
    private NavigationItemCollection $navigation;

    public function __construct(
        private readonly Container $container,
        private Config $config,
        private Request $request,
        private Users $users,
        private Modals $modals,
        private Translations $translations,
        private Assets $assets,
        private EventDispatcher $events,
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
        return $this->config->getString('system.panel.path');
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
        return Uri::normalize(Str::append($this->config->getString('system.panel.root'), '/'));
    }

    /**
     * Get the URI of the panel
     */
    public function panelUri(): string
    {
        return $this->request->root() . ltrim($this->panelRoot(), '/');
    }

    /**
     * Return the current route relative to the panel root
     *
     * @throws UnexpectedValueException If the request URI is outside the panel root
     */
    public function route(): string
    {
        $requestUri = Uri::normalize($this->request->uri());
        if (!Str::startsWith($requestUri, $this->panelRoot())) {
            throw new UnexpectedValueException('The request URI is outside the panel root');
        }
        return '/' . Str::removeStart($requestUri, $this->panelRoot());
    }

    /**
     * Get the panel navigation
     */
    public function navigation(): NavigationItemCollection
    {
        if (isset($this->navigation)) {
            return $this->navigation;
        }

        $translation = $this->translations->getCurrent();

        $this->navigation = NavigationItemCollection::fromArray(
            $this->container->call(require $this->config->getString('system.panel.config.navigation'), [
                'translation' => $translation,
            ])
        );

        $this->events->dispatch(new PanelNavigationLoadedEvent($this->navigation, $translation));

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
     *
     * @deprecated since 2.3.0 Use dependency injection to get the Assets service instead
     */
    public function assets(): Assets
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.3.0. Use dependency injection to get the Assets service instead', __METHOD__), E_USER_DEPRECATED);
        return $this->assets;
    }

    /**
     * Get the actual panel color scheme
     *
     * If the user is logged in and has set a color scheme, it will be used.
     * Otherwise, the default color scheme from the configuration will be used.
     */
    public function colorScheme(): ColorScheme
    {
        $colorScheme = $this->colorSchemeOption();

        if ($colorScheme === ColorScheme::Auto) {
            // Get color scheme from cookie to avoid flash of incorrect color scheme
            return ColorScheme::from($this->request->cookies()->get('formwork_preferred_color_scheme', $colorScheme->value));
        }

        return $colorScheme;
    }

    /**
     * Get compatible color schemes for the current color scheme option
     *
     * @return 'dark'|'light dark'|'light'
     */
    public function compatibleColorSchemes(): string
    {
        return $this->colorSchemeOption()->getCompatibleSchemes();
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

        $path = $this->config->getString('system.translations.paths.panel');

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'yaml') {
                $code = FileSystem::name($file);
                $translations[$code] = LanguageCodes::codeToNativeName($code) . " ({$code})";
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
        return $this->container->call(require $this->config->getString('system.panel.config.app'), [
            'translation' => $this->translations->getCurrent(),
        ]);
    }

    /**
     * Get the color scheme option (user's choice or default from config)
     */
    private function colorSchemeOption(): ColorScheme
    {
        return $this->isLoggedIn()
            ? $this->user()->colorScheme()
            : ColorScheme::from($this->config->getString('system.panel.colorScheme'));
    }
}
