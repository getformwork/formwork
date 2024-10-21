<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Translations\Translations;
use Formwork\Updater\Updater;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;
use Throwable;

class PanelServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        protected Container $container,
        protected Config $config,
        protected ViewFactory $viewFactory,
        protected Request $request,
        protected Schemes $schemes,
        protected Translations $translations,
    ) {
    }

    public function load(Container $container): Panel
    {
        $container->define(AccessLimiter::class)
            ->parameter('registry', new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'accessAttempts.json')))
            ->parameter('limit', $this->config->get('system.panel.loginAttempts'))
            ->parameter('resetTime', $this->config->get('system.panel.loginResetTime'));

        $container->define(Updater::class);

        $this->request->session()->setDuration($this->config->get('system.panel.sessionTimeout') * 60);

        $container->define(ModalFactory::class);

        return $container->build(Panel::class);
    }

    /**
     * @param Panel $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->viewFactory->setMethods($container->call(require FileSystem::joinPaths($this->config->get('system.panel.path'), 'helpers.php')));

        $this->schemes->loadFromPath($this->config->get('system.schemes.paths.panel'));

        $this->translations->loadFromPath($this->config->get('system.translations.paths.panel'));

        if ($service->isLoggedIn()) {
            $this->translations->setCurrent($service->user()->language());
        } else {
            $this->translations->setCurrent($this->config->get('system.panel.translation'));
        }

        if ($service->isLoggedIn() && $this->config->get('system.errors.setHandlers')) {
            $errorsController = $this->container->build(ErrorsController::class);
            set_exception_handler(function (Throwable $throwable) use ($errorsController): never {
                $errorsController->internalServerError($throwable)->prepare($this->request)->send();
                throw $throwable;
            });
        }
    }
}
