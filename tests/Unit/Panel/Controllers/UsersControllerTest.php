<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Files\FileFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\AbstractController;
use Formwork\Panel\Controllers\UsersController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\Role;
use Formwork\Users\RoleCollection;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UsersController::class)]
final class UsersControllerTest extends TestCase
{
    use BuildsPanelControllers;

    private string $accountsPath;

    private UsersController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->accountsPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'accounts');
        FileSystem::createDirectory($this->accountsPath);

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);
    }

    protected function tearDown(): void
    {
        $this->closePanelSession();
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testAdministratorsCanCreateUsersWithAnyRole(): void
    {
        $response = $this->create('admin', $this->newUser(role: 'admin'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastMessageType());
        $this->assertSame('admin', $this->savedUser('newuser')['role']);
    }

    public function testAdministratorsCanCreateUsersWithALimitedRole(): void
    {
        $this->create('admin', $this->newUser(role: 'editor'));

        $this->assertSame('editor', $this->savedUser('newuser')['role']);
    }

    #[DataProvider('submittedRoleProvider')]
    public function testNonAdministratorsCannotAssignARoleDifferentFromTheirOwn(?string $submittedRole): void
    {
        $response = $this->create('editor', $this->newUser(role: $submittedRole));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('success', $this->lastMessageType());
        $this->assertSame('editor', $this->savedUser('newuser')['role']);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function submittedRoleProvider(): iterable
    {
        yield 'administrator' => ['admin'];
        yield 'own role' => ['editor'];
        yield 'missing role' => [null];
    }

    public function testNonAdministratorsCannotCreateAdministratorsBySubmittingTheRoleOfTheForm(): void
    {
        $this->create('editor', $this->newUser(role: 'admin'));

        $this->assertNotSame('admin', $this->savedUser('newuser')['role']);
    }

    public function testCreatingUsersRequiresThePermission(): void
    {
        $response = $this->create('editor', $this->newUser(role: 'editor'), ['panel.users' => false]);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->accountsPath, 'newuser.yaml'));
    }

    public function testNonAdministratorsCannotSubmitUnknownRoles(): void
    {
        $response = $this->create('editor', $this->newUser(role: 'superuser'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->accountsPath, 'newuser.yaml'));
    }

    public function testAdministratorsCannotAssignUnknownRoles(): void
    {
        $response = $this->create('admin', $this->newUser(role: 'superuser'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertFileDoesNotExist(FileSystem::joinPaths($this->accountsPath, 'newuser.yaml'));
    }

    public function testExistingUsernamesAreNotOverwritten(): void
    {
        FileSystem::write(FileSystem::joinPaths($this->accountsPath, 'existing.yaml'), "username: existing\nrole: admin\n");

        $response = $this->create('admin', $this->newUser(role: 'editor', username: 'existing'), existing: ['existing']);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertSame('admin', $this->savedUser('existing')['role']);
    }

    #[DataProvider('invalidUserProvider')]
    public function testInvalidUsersAreNotSaved(array $overrides): void
    {
        $response = $this->create('admin', [...$this->newUser(role: 'editor'), ...$overrides]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastMessageType());
        $this->assertSame([], iterator_to_array(FileSystem::listFiles($this->accountsPath), false));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidUserProvider(): iterable
    {
        yield 'short username' => [['username' => 'ab']];
        yield 'username with a path' => [['username' => '../escape']];
        yield 'username starting with a digit' => [['username' => '1user']];
        yield 'short password' => [['password' => 'short']];
        yield 'invalid email' => [['email' => 'not an email']];
        yield 'missing full name' => [['fullname' => '']];
        yield 'unknown language' => [['language' => 'klingon']];
        yield 'array as role' => [['role' => ['admin']]];
    }

    public function testUsernamesCannotEscapeTheAccountsDirectory(): void
    {
        $this->create('admin', $this->newUser(role: 'editor', username: '../escape'));

        $this->assertFileDoesNotExist(FileSystem::joinPaths(TESTS_TMP_PATH, 'escape.yaml'));
    }

    /**
     * @return array<string, mixed>
     */
    private function newUser(?string $role, string $username = 'newuser'): array
    {
        return array_filter([
            'fullname' => 'New User',
            'username' => $username,
            'password' => 'a-long-password',
            'email'    => 'new@example.test',
            'language' => 'it',
            'role'     => $role,
        ], static fn(mixed $value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, bool>  $permissions
     * @param list<string>         $existing
     */
    private function create(string $currentRole, array $input, array $permissions = ['panel.users' => true], array $existing = []): Response
    {
        $app = App::instance();

        $translations = new Translations($app->config());
        $roles = new RoleCollection([
            'admin'  => new Role('admin', 'Administrator', new Permissions(['panel.users' => true]), $translations),
            'editor' => new Role('editor', 'Editor', new Permissions(['panel.users' => true]), $translations),
        ]);

        $current = $this->createStub(User::class);
        $current->method('permissions')->willReturn(new Permissions($permissions));
        $current->method('isLoggedIn')->willReturn(true);
        $current->method('isAdmin')->willReturn($currentRole === 'admin');
        $current->method('role')->willReturn($roles->get($currentRole));

        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn($current);
        $users->method('roles')->willReturn($roles);
        $users->method('has')->willReturnCallback(static fn(string $username): bool => in_array($username, $existing, true));

        $site = $this->createStub(Site::class);
        $site->method('users')->willReturn($users);

        $fields = $this->fields();
        $modal = $this->createStub(Modal::class);
        $modal->method('fields')->willReturn($fields);
        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $this->controller = $this->makeController(
            UsersController::class,
            $permissions,
            ['users' => ['paths' => ['accounts' => $this->accountsPath]]],
            // Modal fields are named after the modal, so their values are submitted as `newUser[field]`
            ['newUser' => $input],
            [
                Site::class         => $site,
                Modals::class       => $modals,
                FileUploader::class => $this->createStub(FileUploader::class),
                FileFactory::class  => $app->getService(FileFactory::class),
            ],
            $current,
            $users,
        );

        return $this->controller->create(new UserFactory($this->containerOf($this->controller)));
    }

    private function fields(): FieldCollection
    {
        $factory = App::instance()->getService(FieldFactory::class);

        return new FieldCollection([
            'fullname' => $factory->make('newUser.fullname', ['type' => 'text', 'required' => true]),
            'username' => $factory->make('newUser.username', [
                'type'      => 'text',
                'required'  => true,
                'pattern'   => '[a-zA-Z][a-zA-Z0-9]*([\-._][a-zA-Z0-9]+)*',
                'minLength' => 3,
                'maxLength' => 20,
            ]),
            'password' => $factory->make('newUser.password', ['type' => 'password', 'required' => true, 'minLength' => 8]),
            'email'    => $factory->make('newUser.email', ['type' => 'email', 'required' => true]),
            'language' => $factory->make('newUser.language', ['type' => 'select', 'required' => true, 'options' => ['it' => 'Italian', 'de' => 'German']]),
            'role'     => $factory->make('newUser.role', ['type' => 'select', 'default' => 'editor', 'options' => ['admin' => 'Administrator', 'editor' => 'Editor']]),
        ]);
    }

    private function containerOf(UsersController $controller): Container
    {
        $property = new \ReflectionProperty(AbstractController::class, 'container');

        return $property->getValue($controller);
    }

    /**
     * @return array<string, mixed>
     */
    private function savedUser(string $username): array
    {
        return Yaml::parseFile(FileSystem::joinPaths($this->accountsPath, $username . '.yaml'));
    }

    private function lastMessageType(): ?string
    {
        $property = new \ReflectionProperty(AbstractController::class, 'panel');
        $notifications = $property->getValue($this->controller)->notifications();

        return $notifications === [] ? null : end($notifications)['type'];
    }
}
