<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Parsers\Yaml;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\RoleCollection;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;

final class UsersServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    private RoleCollection $roleCollection;

    private Users $users;

    public function __construct(
        private Config $config,
        private Translations $translations,
        private UserFactory $userFactory,
    ) {}

    public function load(Container $container): Users
    {
        return $this->users = new Users([], $this->roleCollection = new RoleCollection());
    }

    public function onResolved(object $service, Container $container): void
    {
        $this->loadRoles();
        $this->loadUsers();
    }

    private function loadRoles(): void
    {
        foreach (FileSystem::listFiles($path = $this->config->get('system.users.paths.roles')) as $file) {
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
        foreach (FileSystem::listFiles($path = $this->config->get('system.users.paths.accounts')) as $file) {
            /**
             * @var array{username: string, fullname: string, hash: string, email: string, language: string, role?: string, image?: string, colorScheme?: string}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $username = $data['username'];
            $this->users->set($username, $this->userFactory->make($data));
        }
    }
}
