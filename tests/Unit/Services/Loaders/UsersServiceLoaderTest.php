<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Closure;
use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Files\FileFactory;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Router\Events\RouteActionResolvedEvent;
use Formwork\Router\Route;
use Formwork\Services\Container;
use Formwork\Services\Loaders\UsersServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Services\Loaders\Fixtures\PermissionActionsFixture;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translations;
use Formwork\Users\Attributes\Permission;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UsersServiceLoader::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class UsersServiceLoaderTest extends TestCase
{
    private string $rolesPath;

    private string $accountsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rolesPath = TESTS_TMP_PATH . '/users/roles';
        $this->accountsPath = TESTS_TMP_PATH . '/users/accounts';
        FileSystem::createDirectory($this->rolesPath, recursive: true);
        FileSystem::createDirectory($this->accountsPath, recursive: true);
    }

    public function testRolesAreLoadedWithTheirPermissions(): void
    {
        FileSystem::write($this->rolesPath . '/admin.yaml', "title: Administrator\npermissions:\n  panel: true\n  panel.users: true\n");
        FileSystem::write($this->rolesPath . '/editor.yaml', "title: Editor\npermissions:\n  panel: true\n  panel.users: false\n");

        $users = $this->resolve();

        $this->assertEqualsCanonicalizing(['admin', 'editor'], $users->roles()->keys());
        $this->assertTrue($users->roles()->get('admin')?->permissions()->has('panel.users'));
        $this->assertSame('Editor', $users->roles()->get('editor')?->title());
    }

    public function testRolesWithoutPermissionsHaveNone(): void
    {
        FileSystem::write($this->rolesPath . '/guest.yaml', "title: Guest\n");

        $role = $this->resolve()->roles()->get('guest');

        $this->assertFalse($role?->permissions()->has('panel'));
    }

    public function testAccountsAreLoadedByUsername(): void
    {
        $this->writeAccount('alice', 'alice');
        $this->writeAccount('bob', 'bob');

        $users = $this->resolve();

        $this->assertEqualsCanonicalizing(['alice', 'bob'], $users->keys());
        $this->assertSame('alice@example.com', $users->get('alice')?->email());
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

    public function testActionsAreKeptWhenTheUserHasTheirPermission(): void
    {
        $restricted = (new PermissionActionsFixture())->restricted(...);

        $this->assertSame($restricted, $this->authorize($restricted, $this->userWith(['panel.test' => true])));
    }

    /**
     * @param array<string, bool> $permissions
     */
    #[DataProvider('missingPermissionsProvider')]
    public function testActionsAreForbiddenWhenTheUserLacksTheirPermission(array $permissions): void
    {
        $restricted = (new PermissionActionsFixture())->restricted(...);

        $this->assertSame(ResponseStatus::Forbidden, $this->authorize($restricted, $this->userWith($permissions))()->status());
    }

    /**
     * @return iterable<string, array{array<string, bool>}>
     */
    public static function missingPermissionsProvider(): iterable
    {
        yield 'without permissions' => [[]];
        yield 'with the permission denied' => [['panel.test' => false]];
        yield 'with another permission' => [['panel.other' => true]];
    }

    public function testActionsAreForbiddenWhenNobodyIsLoggedIn(): void
    {
        $restricted = (new PermissionActionsFixture())->restricted(...);

        $this->assertSame(ResponseStatus::Forbidden, $this->authorize($restricted, null)()->status());
    }

    public function testClosuresCanRequirePermissionsToo(): void
    {
        $restricted = #[Permission('panel.test')] static fn(): string => 'restricted';

        $this->assertSame($restricted, $this->authorize($restricted, $this->userWith(['panel.test' => true])));
        $this->assertSame(ResponseStatus::Forbidden, $this->authorize($restricted, null)()->status());
    }

    public function testActionsWithoutAPermissionAreNeverReplacedNorLoadUsers(): void
    {
        $open = (new PermissionActionsFixture())->open(...);

        $this->assertSame($open, $this->authorize($open, null, usersAreAvailable: false));
    }

    private function writeAccount(string $file, string $username): void
    {
        FileSystem::write($this->accountsPath . "/{$file}.yaml", "username: {$username}\nfullname: " . ucfirst($username) . "\nhash: '\$2y\$10\$abcdefghijklmnopqrstuuOe0oSWJEQ0VPYrTzm/BCUzLYRlCwV7a'\nemail: {$username}@example.com\nlanguage: en\n");
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = $this->createStub(User::class);
        $user->method('permissions')->willReturn(new Permissions($permissions));

        return $user;
    }

    /**
     * Resolve the action of a route with the listener registered when the loader is defined
     */
    private function authorize(Closure $action, ?User $user, bool $usersAreAvailable = true): Closure
    {
        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn($user);

        $errors = $this->createStub(ErrorsControllerInterface::class);
        $errors->method('error')->willReturnCallback(static fn(ResponseStatus $responseStatus): Response => new Response('Error', $responseStatus));

        $events = new EventDispatcher();

        $container = new Container();
        $container->define(EventDispatcher::class, $events);
        $container->define(Users::class, $usersAreAvailable ? $users : static fn() => throw new LogicException('Users must not be resolved'));
        $container->define(ErrorsControllerInterface::class, $errors);

        UsersServiceLoader::onDefined($container);

        return $events->dispatch(new RouteActionResolvedEvent(new Route('test', '/test/'), $action))->action();
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
        $container->define(EventDispatcher::class);
        $container->define(Config::class, $config);
        $container->define(Request::class, new Request(server: ['REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80']));
        $container->define(Translations::class, new Translations($config));
        $container->define(FileFactory::class, App::instance()->getService(FileFactory::class));
        $container->define(UserFactory::class);
        $container->define(Users::class)->loader(UsersServiceLoader::class);

        return $container;
    }
}
