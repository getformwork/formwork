<?php

namespace Formwork\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Authentication\RateLimiter;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Log\Logger;
use Formwork\Log\Registry;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Panel;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Translations\Translations;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;

final class PanelServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config,
        private ViewFactory $viewFactory,
        private Request $request,
        private Schemes $schemes,
        private Translations $translations,
        private Assets $assets,
        private Logger $logger,
        private EventDispatcher $eventDispatcher,
    ) {}

    public function load(Container $container): Panel
    {
        if ($this->config->has('system.panel.loginAttempts') || $this->config->has('system.panel.loginResetTime')) {
            if ($this->config->has('system.panel.loginAttempts')) {
                trigger_error('The "system.panel.loginAttempts" configuration option is deprecated since Formwork 2.3.0. Use "system.authentication.limits.maxAttempts" instead.', E_USER_DEPRECATED);
            }

            if ($this->config->has('system.panel.loginResetTime')) {
                trigger_error('The "system.panel.loginResetTime" configuration option is deprecated since Formwork 2.3.0. Use "system.authentication.limits.resetTime" instead.', E_USER_DEPRECATED);
            }

            $container->define(RateLimiter::class)
                ->parameter('registry', new Registry(FileSystem::joinPaths($this->config->get('system.authentication.registryPath'), 'accessAttempts.json')))
                ->parameter('limit', $this->config->get('system.panel.loginAttempts', $this->config->get('system.authentication.limits.maxAttempts')))
                ->parameter('resetTime', $this->config->get('system.panel.loginResetTime', $this->config->get('system.authentication.limits.resetTime')));

            $container->resolve(RateLimiter::class);
        }

        $container->define(Updater::class)
            ->parameter('options', $this->config->get('system.updates'));

        if ($this->config->has('system.panel.sessionTimeout')) {
            trigger_error('The "system.panel.sessionTimeout" configuration option (in minutes) is deprecated since Formwork 2.3.0. Use "system.session.duration" (in seconds) instead.', E_USER_DEPRECATED);
            $this->request->session()->setDuration($this->config->get('system.panel.sessionTimeout') * 60);
        }

        $container->define(ModalFactory::class);
        $container->define(Modals::class);

        $this->eventDispatcher->on('panelLoggedIn', $this->onPanelLoggedIn(...));

        return $container->build(Panel::class);
    }

    /**
     * @param Panel $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->viewFactory->setResolutionPaths(['panel' => $this->config->get('system.views.paths.panel')]);
        $this->viewFactory->setMethods($container->call(require $this->config->get('system.views.methods.panel')));

        $this->assets->setResolutionPaths(['panel' => [
            'path' => $this->config->get('system.panel.paths.assets'),
            'uri'  => $service->uri('/assets/'),
        ]]);

        $this->schemes->loadFromPath($this->config->get('system.schemes.paths.panel'));

        $this->translations->loadFromPath($this->config->get('system.translations.paths.panel'));

        // Resolve site to avoid panel language to be changed after
        $container->get(Site::class);

        if ($service->isLoggedIn()) {
            $this->translations->setCurrent($service->user()->language());
        } else {
            $this->translations->setCurrent($this->config->get('system.panel.translation'));
        }

        if ($service->isLoggedIn()) {
            $container->define(ErrorsController::class)
                ->alias(ErrorsControllerInterface::class)
                ->lazy(false);
        }
    }

    private function onPanelLoggedIn(PanelLoggedInEvent $panelLoggedInEvent): void
    {
        $this->logger->info('Panel user {username} logged in', ['username' => $panelLoggedInEvent->user()->username()]);
    }
}
