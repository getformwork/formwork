<?php

namespace Formwork\Services\Loaders;

use Formwork\Config;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Updater;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;

class PanelServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(protected Config $config, protected ViewFactory $viewFactory, protected Request $request)
    {
    }

    public function load(Container $container): Panel
    {
        $container->define(AccessLimiter::class)
            ->parameter('registry', new Registry(FileSystem::joinPaths($this->config->get('system.panel.paths.logs'), 'accessAttempts.json')))
            ->parameter('limit', $this->config->get('system.panel.loginAttempts'))
            ->parameter('resetTime', $this->config->get('system.panel.loginResetTime'));

        $container->define(Updater::class);

        $this->request->session()->setDuration($this->config->get('system.panel.sessionTimeout') * 60);

        return $container->build(Panel::class);
    }

    /**
     * @param Panel $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->viewFactory->setMethods($container->call(require FileSystem::joinPaths($this->config->get('system.panel.path'), 'helpers.php')));

        $service->load();
    }
}
