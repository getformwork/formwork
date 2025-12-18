<?php

namespace Formwork\Services\Loaders;

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\AccessLimiter;
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
    ) {}

    public function load(Container $container): Panel
    {
        $container->define(AccessLimiter::class)
            ->parameter('registry', new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'accessAttempts.json')))
            ->parameter('limit', $this->config->get('system.panel.loginAttempts'))
            ->parameter('resetTime', $this->config->get('system.panel.loginResetTime'));

        $container->define(Updater::class)
            ->parameter('options', $this->config->get('system.updates'));

        $this->request->session()->setDuration($this->config->get('system.panel.sessionTimeout') * 60);

        $container->define(ModalFactory::class);
        $container->define(Modals::class);

        return $container->build(Panel::class);
    }

    /**
     * @param Panel $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->viewFactory->setMethods($container->call(require $this->config->get('system.views.methods.panel')));

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
}
