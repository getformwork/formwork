<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Services\Loaders\UsersServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UsersServiceLoader::class)]
final class UsersServiceLoaderTest extends TestCase
{
    private string $rolesPath;

    private string $accountsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->rolesPath = TESTS_TMP_PATH . '/users/roles';
        $this->accountsPath = TESTS_TMP_PATH . '/users/accounts';
        FileSystem::createDirectory($this->rolesPath, recursive: true);
        FileSystem::createDirectory($this->accountsPath, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testRolesAreLoadedWithTheirPermissions(): void
    {
        FileSystem::write($this->rolesPath . '/admin.yaml', "title: Administrator\npermissions:\n  panel: true\n  panel.users: true\n");
        FileSystem::write($this->rolesPath . '/editor.yaml', "title: Editor\npermissions:\n  panel: true\n  panel.users: false\n");

        $users = $this->resolve();

        $this->assertSame(['admin', 'editor'], array_keys($users->roles()->toArray()));
        $this->assertTrue($users->roles()->get('admin')->permissions()->has('panel.users'));
        $this->assertSame('Editor', $users->roles()->get('editor')->title());
    }

    public function testRolesWithoutPermissionsHaveNone(): void
    {
        FileSystem::write($this->rolesPath . '/guest.yaml', "title: Guest\n");

        $role = $this->resolve()->roles()->get('guest');

        $this->assertFalse($role->permissions()->has('panel'));
    }

    public function testAccountsAreLoadedByUsername(): void
    {
        $this->writeAccount('alice', 'alice');
        $this->writeAccount('bob', 'bob');

        $users = $this->resolve();

        $this->assertSame(['alice', 'bob'], array_keys($users->toArray()));
        $this->assertSame('alice@example.com', $users->get('alice')->email());
    }

    public function testAccountsAreIndexedByTheUsernameStoredInsideTheFile(): void
    {
        $this->writeAccount('renamed', 'original');

        $users = $this->resolve();

        $this->assertTrue($users->has('original'));
        $this->assertFalse($users->has('renamed'));
    }

    public function testEmptyDirectoriesProduceAnEmptyCollection(): void
    {
        $users = $this->resolve();

        $this->assertCount(0, $users);
    }

    public function testNonYamlFilesInTheAccountsDirectoryAreIgnored(): void
    {
        $this->writeAccount('alice', 'alice');
        FileSystem::write($this->accountsPath . '/.gitkeep', '');
        FileSystem::write($this->accountsPath . '/notes.txt', 'not an account');

        $this->assertSame(['alice'], array_keys($this->resolve()->toArray()));
    }

    public function testNonYamlFilesInTheRolesDirectoryAreIgnored(): void
    {
        FileSystem::write($this->rolesPath . '/admin.yaml', "title: Administrator\n");
        FileSystem::write($this->rolesPath . '/README.md', '# Roles');

        $this->assertSame(['admin'], array_keys($this->resolve()->roles()->toArray()));
    }

    public function testUsersAreResolvedOnlyOnce(): void
    {
        $container = $this->container();

        $this->assertSame($container->get(Users::class), $container->get(Users::class));
    }

    private function writeAccount(string $file, string $username): void
    {
        FileSystem::write($this->accountsPath . "/{$file}.yaml", "username: {$username}\nfullname: " . ucfirst($username) . "\nhash: '\$2y\$10\$abcdefghijklmnopqrstuuOe0oSWJEQ0VPYrTzm/BCUzLYRlCwV7a'\nemail: {$username}@example.com\nlanguage: en\n");
    }

    private function resolve(): Users
    {
        return $this->container()->get(Users::class);
    }

    private function container(): Container
    {
        $config = new Config(['system' => [
            'translations' => ['fallback' => 'en'],
            'users'        => ['paths' => ['roles' => $this->rolesPath, 'accounts' => $this->accountsPath, 'images' => TESTS_TMP_PATH . '/users/images']],
        ]], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, $config);
        $container->define(Request::class, new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']));
        $container->define(Translations::class, new Translations($config));
        $container->define(FileFactory::class, App::instance()->getService(FileFactory::class));
        $container->define(UserFactory::class);
        $container->define(Users::class)->loader(UsersServiceLoader::class);

        return $container;
    }
}
