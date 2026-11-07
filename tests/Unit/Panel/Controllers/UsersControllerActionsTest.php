<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\FileResponse;
use Formwork\Http\Files\UploadedFile;
use Formwork\Http\Response;
use Formwork\Panel\Controllers\UsersController;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsUploads;
use Formwork\Tests\Unit\Users\BuildsPasswordHashes;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
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
    use BuildsUploads;
    use BuildsPasswordHashes;

    private string $imagesPath;

    /**
     * @var list<string>
     */
    private array $existingUserImages = [];

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

        $this->existingUserImages = glob($this->userImagesPath() . '/*') ?: [];

        $secretFile = TESTS_TMP_PATH . '/secret.txt';
        FileSystem::write($secretFile, 'secret');
    }

    protected function tearDown(): void
    {
        // Images of the users are stored in the images directory of the application
        foreach (glob($this->userImagesPath() . '/*') ?: [] as $image) {
            if (!in_array($image, $this->existingUserImages, true)) {
                FileSystem::delete($image);
            }
        }

        foreach (['alice', 'bob', 'carol'] as $username) {
            if (FileSystem::exists($this->accountFile($username))) {
                FileSystem::delete($this->accountFile($username));
            }
        }

        $this->closePanelSession();
        parent::tearDown();
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('deniedActionProvider')]
    public function testActionsRequireTheirPermission(string $action, string $permission, array $params): void
    {
        $this->assertOutcome(['status' => 403], $this->call($this->controller([$permission => false]), $action, $params));
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function deniedActionProvider(): iterable
    {
        yield 'index' => ['index', 'panel.users.index', []];
        yield 'delete' => ['delete', 'panel.users.delete', ['user' => 'bob']];
        yield 'deleteImage' => ['deleteImage', 'panel.users.deleteImage', ['user' => 'bob']];
        yield 'profile' => ['profile', 'panel.users.profile', ['user' => 'bob']];
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed>  $methods  Values returned by the methods of the current user
     * @param array<string, mixed>  $expected
     * @param array<string, bool>   $accounts Whether the account files must exist after the action
     * @param array<string, string> $server
     */
    #[DataProvider('actionOutcomeProvider')]
    public function testActionsRespondAsExpected(string $action, array $params, array $methods, array $expected, array $accounts = [], array $server = []): void
    {
        $controller = $this->controller(user: $this->stubUser(['panel.users' => true], $methods), server: $server);

        $this->assertOutcome($expected, $this->call($controller, $action, $params));

        foreach ($accounts as $username => $exists) {
            $exists ? $this->assertFileExists($this->accountFile($username)) : $this->assertFileDoesNotExist($this->accountFile($username));
        }
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function actionOutcomeProvider(): iterable
    {
        $get = ['REQUEST_METHOD' => 'GET'];

        yield 'index' => ['index', [], [], ['status' => 200]];
        yield 'unknown users cannot be deleted' => ['delete', ['user' => 'missing'], [], ['to' => 'panel.users', 'notices' => ['error']]];
        yield 'users that cannot be deleted are kept' => ['delete', ['user' => 'bob'], ['canDeleteUser' => false], ['notices' => ['error']], ['bob' => true]];
        yield 'users can be deleted' => ['delete', ['user' => 'bob'], ['canDeleteUser' => true], ['to' => 'panel.users', 'notices' => ['success']], ['bob' => false, 'alice' => true]];
        yield 'images of unknown users cannot be deleted' => ['deleteImage', ['user' => 'missing'], [], ['notices' => ['error']]];
        yield 'images of users that cannot be changed are kept' => ['deleteImage', ['user' => 'bob'], ['canChangeOptionsOf' => false], ['to' => 'panel.users.profile', 'notices' => ['error']]];
        yield 'unknown profiles redirect to the users' => ['profile', ['user' => 'missing'], [], ['to' => 'panel.users', 'notices' => ['error']]];
        yield 'profiles that cannot be changed are not saved' => ['profile', ['user' => 'bob'], ['canChangeOptionsOf' => false], ['notices' => ['error']]];
        yield 'profile' => ['profile', ['user' => 'bob'], [], ['status' => 200], [], $get];
    }

    public function testImagesAreServedFromTheImagesDirectory(): void
    {
        $response = $this->controller()->images(new RouteParams(['image' => 'alice.png']));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->imagesPath . '/alice.png', $this->responseFile($response));
        $this->assertSame('private, max-age=31536000, immutable', $response->headers()->get('Cache-Control'));
    }

    #[DataProvider('unservedImageProvider')]
    public function testOnlyExistingImagesOfTheImagesDirectoryAreServed(string $image): void
    {
        $this->assertNotInstanceOf(FileResponse::class, $this->controller()->images(new RouteParams(['image' => $image])));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unservedImageProvider(): iterable
    {
        yield 'missing' => ['missing.png'];
        yield 'absolute path' => ['/etc/passwd'];
    }

    #[DataProvider('escapingImageProvider')]
    public function testImagesCannotLeaveTheImagesDirectory(string $image): void
    {
        $this->assertNotInstanceOf(FileResponse::class, $this->controller()->images(new RouteParams(['image' => $image])));
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
    }

    /**
     * @param array<string, string> $submitted
     * @param ?list<string>         $notices
     */
    #[DataProvider('profileSaveProvider')]
    public function testProfilesAreSavedOnlyWithAValidImage(array $submitted, ?string $image, ?array $notices, bool $saved): void
    {
        $upload = match ($image) {
            'png'   => $this->uploadedImage('image', 'avatar.png'),
            'text'  => $this->uploadedText('image', 'avatar.txt'),
            null    => null,
            default => $this->failedUpload((int) $image, 'image', 'avatar.png'),
        };

        $this->assertOutcome(['to' => 'panel.users.profile'] + ($notices === null ? [] : ['notices' => $notices]), $this->saveProfile($submitted, $upload));

        $account = Yaml::parseFile($this->accountFile('alice'));

        $this->assertSame($saved, ($account['fullname'] ?? null) === 'Alice Changed');
        $this->assertSame($image === 'png', isset($account['image']));
    }

    /**
     * @return iterable<string, array{array<string, string>, ?string, ?list<string>, bool}>
     */
    public static function profileSaveProvider(): iterable
    {
        $changed = ['fullname' => 'Alice Changed'];

        yield 'with an image' => [$changed, 'png', ['success', 'success'], true];
        yield 'without an image' => [$changed, null, ['success'], true];
        yield 'with an image of a disallowed type' => [$changed, 'text', ['error'], false];

        foreach (self::uploadErrorProvider() as $name => [$error]) {
            yield "with an image failing with a PHP error ($name)" => [$changed, (string) $error, ['error'], false];
        }
    }

    public function testUploadedImagesAreSquared(): void
    {
        $this->saveProfile([], $this->uploadedImage('image', 'avatar.png'));

        $account = Yaml::parseFile($this->accountFile('alice'));
        [$width, $height] = getimagesize($this->userImagesPath() . '/' . $account['image']) ?: [0, 0];

        $this->assertSame($width, $height);
        $this->assertSame(App::instance()->config()->getInt('system.panel.userImageSize'), $width);
    }

    /**
     * @param array<string, string> $overrides
     */
    private function saveProfile(array $overrides, ?UploadedFile $image): Response
    {
        $uploader = $this->uploader([$this->userImagesPath()]);
        $site = $this->createStub(Site::class);
        $site->method('users')->willReturn($this->users());

        $controller = $this->makeController(
            UsersController::class,
            ['panel.users' => true],
            ['users' => ['paths' => ['accounts' => $this->accountsPath(), 'images' => $this->userImagesPath()]]],
            ['fullname' => 'Alice', 'email' => 'alice@example.test', 'language' => 'en', 'role' => 'admin', 'colorScheme' => 'light', ...$overrides],
            [Site::class => $site, FileUploader::class => $uploader],
            $this->users()->get('bob'),
            server: ['REQUEST_METHOD' => 'POST'],
            files: $image === null ? [] : ['image' => $image],
        );

        return $controller->profile(new RouteParams(['user' => 'alice']), $uploader);
    }

    /**
     * Create users and their account files, and return the users
     */
    private function users(): Users
    {
        if ($this->users !== null) {
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
                'passwordHash' => self::hashPassword('password'),
            ]);

            FileSystem::write($this->accountFile($username), "username: {$username}\n");
            $users[$username] = $user;
        }

        return $this->users = new Users($users, $app->getService(Users::class)->roles());
    }

    private function userImagesPath(): string
    {
        return App::instance()->config()->getString('system.users.paths.images');
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
            system: ['users' => ['paths' => ['accounts' => $this->accountsPath(), 'images' => $this->imagesPath]]],
            input: $input,
            services: [Site::class => $site],
            user: $user,
            server: $server,
        );
    }
}
