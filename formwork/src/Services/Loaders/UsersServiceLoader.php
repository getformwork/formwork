<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Parsers\Yaml;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\RoleCollection;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;

class UsersServiceLoader implements ServiceLoaderInterface
{
    protected RoleCollection $roleCollection;

    protected Users $users;

    public function __construct(
        protected Container $container,
        protected Config $config,
        protected Translations $translations,
        protected UserFactory $userFactory
    ) {
    }

    public function load(Container $container): Users
    {
        $this->loadRoles();
        $this->loadUsers();
        return $this->users;
    }

    protected function loadRoles(): void
    {
        $this->roleCollection = new RoleCollection();
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
        $this->users = new Users([], $this->roleCollection);
        foreach (FileSystem::listFiles($path = $this->config->get('system.users.paths.accounts')) as $file) {
            /**
             * @var array{username: string, fullname: string, hash: string, email: string, language: string, role?: string, image?: string, colorScheme?: string}
             */
            $data = Yaml::parseFile(FileSystem::joinPaths($path, $file));
            $role = $this->roleCollection->get($data['role'] ?? 'user');
            $username = $data['username'];
            $this->users->set($username, $this->userFactory->make($data, $role));
        }
    }
}
