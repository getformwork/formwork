<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Parsers\Yaml;
use Formwork\Router\Events\RouteActionResolvedEvent;
use Formwork\Services\Container;
use Formwork\Services\DefinitionAwareServiceLoaderInterface;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Translations\Translations;
use Formwork\Users\Attributes\Permission;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\RoleCollection;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use ReflectionFunction;

final class UsersServiceLoader implements DefinitionAwareServiceLoaderInterface, ResolutionAwareServiceLoaderInterface
{
    private RoleCollection $roleCollection;

    private Users $users;

    public function __construct(
        private Config $config,
        private Translations $translations,
        private UserFactory $userFactory,
    ) {}

    /**
     * Respond with the forbidden error to the actions requiring a permission the logged in user lacks
     *
     * @since 2.4.0
     */
    public static function onDefined(Container $container): void
    {
        $container->get(EventDispatcher::class)->on('routeActionResolved', static function (RouteActionResolvedEvent $routeActionResolvedEvent) use ($container): void {
            $attributes = (new ReflectionFunction($routeActionResolvedEvent->action()))->getAttributes(Permission::class);

            if ($attributes === []) {
                return;
            }

            $user = $container->get(Users::class)->loggedIn();

            if ($user === null || !$user->permissions()->has($attributes[0]->newInstance()->name)) {
                $routeActionResolvedEvent->setAction(static fn(): Response => $container->get(ErrorsControllerInterface::class)->error(ResponseStatus::Forbidden));
            }
        });
    }

    public function load(Container $container): Users
    {
        return $this->users = new Users([], $this->roleCollection = new RoleCollection());
    }

    /**
     * @since 2.3.0
     */
    public function onResolved(object $service, Container $container): void
    {
        $this->loadRoles();
        $this->loadUsers();
    }

    private function loadRoles(): void
    {
        foreach (FileSystem::listFiles($path = $this->config->getString('system.users.paths.roles')) as $file) {
            if (FileSystem::extension($file) !== 'yaml') {
                continue;
            }
            /**
             * @var array{title: string, permissions?: array<string, bool>}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $id = FileSystem::name($file);
            $permissions = new Permissions($data['permissions'] ?? []);
            $this->roleCollection->set($id, new Role($id, $data['title'], $permissions, $this->translations));
        }
    }

    private function loadUsers(): void
    {
        foreach (FileSystem::listFiles($path = $this->config->getString('system.users.paths.accounts')) as $file) {
            if (FileSystem::extension($file) !== 'yaml') {
                continue;
            }
            /**
             * @var array{username: string, fullname: string, hash: string, email: string, language: string, role?: string, image?: string, colorScheme?: string}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $username = $data['username'];
            $this->users->set($username, $this->userFactory->make($data));
        }
    }
}
