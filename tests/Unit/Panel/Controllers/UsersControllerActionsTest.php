<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\UsersController;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(UsersController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class UsersControllerActionsTest extends TestCase
{
    use BuildsPanelControllers;

    private string $imagesPath;

    private string $secretFile;

    private ?Users $users = null;

    protected function setUp(): void
    {
        parent::setUp();

        // `FieldFactory` is registered lazily by the schemes service loader
        App::instance()->getService(Schemes::class);

        $this->imagesPath = TESTS_TMP_PATH . '/images';
        FileSystem::createDirectory($this->imagesPath);
        FileSystem::write($this->imagesPath . '/alice.png', 'image');
        FileSystem::createDirectory(TESTS_TMP_PATH . '/images/nested');

        $this->secretFile = TESTS_TMP_PATH . '/secret.txt';
        FileSystem::write($this->secretFile, 'secret');
    }

    protected function tearDown(): void
    {
        foreach (['alice', 'bob', 'carol'] as $username) {
            if (FileSystem::exists($this->accountFile($username))) {
                FileSystem::delete($this->accountFile($username));
            }
        }

        $this->closePanelSession();
        parent::tearDown();
    }

    public function testIndexRequiresThePermission(): void
    {
        $response = $this->controller(['panel.users.index' => false])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testIndexListsTheUsersSortedByUsername(): void
    {
        $view = $this->renderedView($this->controller()->index());

        $this->assertSame('@panel.users.index', $view['view']);
        $this->assertSame(['alice', 'bob', 'carol'], array_keys($view['vars']['users']));
    }

    public function testDeleteRequiresThePermission(): void
    {
        $response = $this->controller(['panel.users.delete' => false])->delete(new RouteParams(['user' => 'bob']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
        $this->assertTrue($this->users()->has('bob'));
    }

    public function testUnknownUsersCannotBeDeleted(): void
    {
        $response = $this->controller()->delete(new RouteParams(['user' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testUsersThatCannotBeDeletedAreKept(): void
    {
        $current = $this->createStub(User::class);
        $current->method('permissions')->willReturn(new Permissions(['panel.users.delete' => true]));
        $current->method('canDeleteUser')->willReturn(false);

        $response = $this->controller(user: $current)->delete(new RouteParams(['user' => 'bob']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertFileExists($this->accountFile('bob'));
    }

    public function testUsersCanBeDeleted(): void
    {
        $current = $this->createStub(User::class);
        $current->method('permissions')->willReturn(new Permissions(['panel.users.delete' => true]));
        $current->method('canDeleteUser')->willReturn(true);

        $response = $this->controller(user: $current)->delete(new RouteParams(['user' => 'bob']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.users', $response->headers()->get('Location'));
        $this->assertSame('success', $this->lastNotificationType());
        $this->assertFileDoesNotExist($this->accountFile('bob'));
        $this->assertFileExists($this->accountFile('alice'));
    }

    public function testDeletingImagesRequiresThePermission(): void
    {
        $response = $this->controller(['panel.users.deleteImage' => false])->deleteImage(new RouteParams(['user' => 'bob']));

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testImagesOfUnknownUsersCannotBeDeleted(): void
    {
        $response = $this->controller()->deleteImage(new RouteParams(['user' => 'missing']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testImagesOfUsersWhoseOptionsCannotBeChangedAreKept(): void
    {
        $current = $this->createStub(User::class);
        $current->method('permissions')->willReturn(new Permissions(['panel.users.deleteImage' => true]));
        $current->method('canChangeOptionsOf')->willReturn(false);

        $response = $this->controller(user: $current)->deleteImage(new RouteParams(['user' => 'bob']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertStringEndsWith('/route/panel.users.profile', $response->headers()->get('Location'));
    }

    public function testProfileRequiresThePermission(): void
    {
        $response = $this->controller(['panel.users.profile' => false])->profile(new RouteParams(['user' => 'bob']), $this->fileUploader());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUnknownProfilesRedirectToTheUsers(): void
    {
        $response = $this->controller()->profile(new RouteParams(['user' => 'missing']), $this->fileUploader());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.users', $response->headers()->get('Location'));
        $this->assertSame('error', $this->lastNotificationType());
    }

    public function testProfileIsDisplayedWithItsNeighbours(): void
    {
        $response = $this->controller(server: ['REQUEST_METHOD' => 'GET'])->profile(new RouteParams(['user' => 'bob']), $this->fileUploader());
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.users.profile', $view['view']);
        $this->assertSame('bob', $view['vars']['user']['username']);
        $this->assertSame('alice', $view['vars']['previousUser']['username']);
        $this->assertSame('carol', $view['vars']['nextUser']['username']);
    }

    public function testTheFirstProfileHasNoPreviousUser(): void
    {
        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->profile(new RouteParams(['user' => 'alice']), $this->fileUploader()));

        $this->assertNull($view['vars']['previousUser']);
        $this->assertSame('bob', $view['vars']['nextUser']['username']);
    }

    public function testTheLastProfileHasNoNextUser(): void
    {
        $view = $this->renderedView($this->controller(server: ['REQUEST_METHOD' => 'GET'])->profile(new RouteParams(['user' => 'carol']), $this->fileUploader()));

        $this->assertSame('bob', $view['vars']['previousUser']['username']);
        $this->assertNull($view['vars']['nextUser']);
    }

    public function testProfilesThatCannotBeChangedAreNotSaved(): void
    {
        $current = $this->createStub(User::class);
        $current->method('permissions')->willReturn(new Permissions(['panel.users.profile' => true]));
        $current->method('canChangeOptionsOf')->willReturn(false);

        $response = $this->controller(user: $current, input: ['fullname' => 'Changed'], server: ['REQUEST_METHOD' => 'POST'])
            ->profile(new RouteParams(['user' => 'bob']), $this->fileUploader());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('error', $this->lastNotificationType());
        $this->assertStringNotContainsString('Changed', FileSystem::read($this->accountFile('bob')));
    }

    public function testImagesAreServedFromTheImagesDirectory(): void
    {
        $response = $this->controller()->images(new RouteParams(['image' => 'alice.png']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->imagesPath . '/alice.png', $this->responseFile($response));
        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    public function testMissingImagesAreNotFound(): void
    {
        $response = $this->controller()->images(new RouteParams(['image' => 'missing.png']));

        $this->assertNotInstanceOf(FileResponse::class, $response);
        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    #[DataProvider('escapingImageProvider')]
    public function testImagesCannotLeaveTheImagesDirectory(string $image): void
    {
        $response = $this->controller()->images(new RouteParams(['image' => $image]));

        $this->assertNotInstanceOf(FileResponse::class, $response);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function escapingImageProvider(): iterable
    {
        yield 'parent directory' => ['../secret.txt'];
        yield 'parent directory with a Windows separator' => ['..\secret.txt'];
        yield 'deep traversal' => [str_repeat('../', 20) . 'etc/passwd'];
        yield 'traversal after a directory' => ['nested/../../secret.txt'];
        yield 'absolute path' => ['/etc/passwd'];
    }

    /**
     * Create users and their account files, and return the users
     */
    private function users(): Users
    {
        if (isset($this->users)) {
            return $this->users;
        }

        $app = App::instance();
        $factory = $app->getService(UserFactory::class);

        if (!FileSystem::isDirectory($this->accountsPath(), assertExists: false)) {
            FileSystem::createDirectory($this->accountsPath(), recursive: true);
        }

        $users = [];

        foreach (['alice', 'bob', 'carol'] as $username) {
            $user = $factory->make([
                'username'     => $username,
                'fullname'     => ucfirst($username),
                'email'        => "{$username}@example.test",
                'role'         => 'admin',
                'language'     => 'en',
                'passwordHash' => password_hash('password', PASSWORD_DEFAULT),
            ]);

            FileSystem::write($this->accountFile($username), "username: {$username}\n");
            $users[$username] = $user;
        }

        return $this->users = new Users($users, $app->getService(Users::class)->roles());
    }

    private function accountsPath(): string
    {
        // Users are deleted from the accounts path of the application
        return App::instance()->config()->getString('system.users.paths.accounts');
    }

    private function accountFile(string $username): string
    {
        return $this->accountsPath() . "/{$username}.yaml";
    }

    private function fileUploader(): FileUploader
    {
        return $this->createStub(FileUploader::class);
    }

    private function lastNotificationType(): ?string
    {
        $messages = $this->panelRequest?->session()->messages()->getAll() ?? [];

        return array_key_last($messages);
    }

    private function responseFile(FileResponse $fileResponse): string
    {
        return (string) (new ReflectionProperty(FileResponse::class, 'path'))->getValue($fileResponse);
    }

    /**
     * @param array<string, bool>   $permissions
     * @param array<string, mixed>  $input
     * @param array<string, string> $server
     */
    private function controller(array $permissions = ['panel.users' => true], ?User $user = null, array $input = [], array $server = []): UsersController
    {
        $site = $this->createStub(Site::class);
        $site->method('users')->willReturn($this->users());

        return $this->makeController(
            UsersController::class,
            $permissions,
            ['users' => ['paths' => ['accounts' => $this->accountsPath(), 'images' => $this->imagesPath]]],
            $input,
            [Site::class => $site],
            $user,
            server: $server,
        );
    }
}
