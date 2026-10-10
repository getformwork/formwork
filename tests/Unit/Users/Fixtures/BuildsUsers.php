<?php

namespace Formwork\Tests\Unit\Users\Fixtures;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\RoleCollection;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;

/**
 * @mixin \Formwork\Tests\TestCase
 */
trait BuildsUsers
{
    protected string $accountsPath;

    protected string $imagesPath;

    protected function setUpUsers(): void
    {
        $this->setUpTempDirectory();
        $this->accountsPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'users', 'sandbox', 'accounts');
        $this->imagesPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'users', 'sandbox', 'images');
        FileSystem::createDirectory($this->accountsPath, recursive: true);
        FileSystem::createDirectory($this->imagesPath, recursive: true);
    }

    protected function roles(): RoleCollection
    {
        $translations = new Translations(new Config(['system' => ['translations' => ['fallback' => 'en']]], resolved: true));

        return new RoleCollection([
            'admin'  => new Role('admin', 'Administrator', new Permissions(['panel' => true]), $translations),
            'editor' => new Role('editor', 'Editor', new Permissions(['panel' => true, 'panel.users' => false, 'panel.options' => false]), $translations),
            'user'   => new Role('user', 'User', new Permissions(['panel' => false]), $translations),
        ]);
    }

    protected function users(): Users
    {
        return new Users([], $this->roles());
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeUser(array $data, ?Users $users = null): TestableUser
    {
        $users ??= $this->users();

        $config = new Config(['system' => ['users' => ['paths' => ['accounts' => $this->accountsPath, 'images' => $this->imagesPath]]]], resolved: true);
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']);

        $user = new TestableUser($data, $config, $request, App::instance()->getService(FileFactory::class), $users);

        if (isset($data['username'])) {
            $users->set($data['username'], $user);
        }

        return $user;
    }
}
