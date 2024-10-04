<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\AccessLimiter;
use Formwork\Panel\Users\Permissions;
use Formwork\Panel\Users\Role;
use Formwork\Panel\Users\RoleCollection;
use Formwork\Panel\Users\UserCollection;
use Formwork\Panel\Users\UserFactory;
use Formwork\Parsers\Yaml;
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
    protected RoleCollection $roleCollection;

    protected UserCollection $userCollection;

    public function __construct(
        protected Container $container,
        protected Config $config,
        protected ViewFactory $viewFactory,
        protected Request $request,
        protected Schemes $schemes,
        protected Translations $translations,
        protected UserFactory $userFactory
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

        $this->roleCollection = new RoleCollection();

        $this->userCollection = new UserCollection([], $this->roleCollection);

        return $container->build(Panel::class, ['userCollection' => $this->userCollection]);
    }

    /**
     * @param Panel $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->viewFactory->setMethods($container->call(require FileSystem::joinPaths($this->config->get('system.panel.path'), 'helpers.php')));

        $this->schemes->loadFromPath($this->config->get('system.schemes.paths.panel'));

        $this->translations->loadFromPath($this->config->get('system.translations.paths.panel'));

        $this->loadRoles();

        $this->loadUsers();

        if ($service->isLoggedIn()) {
            $this->translations->setCurrent($service->user()->language());
        } else {
            $this->translations->setCurrent($this->config->get('system.panel.translation'));
        }

        if ($service->isLoggedIn() && $this->config->get('system.errors.setHandlers')) {
            $errorsController = $this->container->build(ErrorsController::class);
            set_exception_handler(function (Throwable $throwable) use ($errorsController): never {
                $errorsController->internalServerError($throwable)->send();
                throw $throwable;
            });
        }
    }

    protected function loadRoles(): void
    {
        foreach (FileSystem::listFiles($path = $this->config->get('system.users.paths.roles')) as $file) {
            /**
             * @var array{title: string, permissions: array<string, bool>}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $id = FileSystem::name($file);
            $permissions = new Permissions($data['permissions']);
            $this->roleCollection->set($id, new Role($id, $data['title'], $permissions, $this->translations));
        }
    }

    protected function loadUsers(): void
    {
        foreach (FileSystem::listFiles($path = $this->config->get('system.users.paths.accounts')) as $file) {
            /**
             * @var array{username: string, fullname: string, hash: string, email: string, language: string, role?: string, image?: string, colorScheme?: string}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $role = $this->roleCollection->get($data['role'] ?? 'user');
            $username = $data['username'];
            $this->userCollection->set($username, $this->userFactory->make($data, $role));
        }
    }
}
