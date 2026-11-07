<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Events\EventDispatcher;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\RegisterController;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(RegisterController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class RegisterControllerTest extends TestCase
{
    use BuildsPanelControllers;

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testRegistrationIsNotAvailableWhenUsersExist(): void
    {
        $response = $this->register(usersExist: true);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testUsersAreNotCreatedWhenUsersExist(): void
    {
        $users = $this->createMock(Users::class);
        $users->expects($this->never())->method('set');

        $this->register(self::validInput(), usersExist: true, users: $users);

        $this->assertDirectoryDoesNotExist($this->accountsPath() . '/newadmin.yaml');
        $this->assertFileDoesNotExist($this->accountsPath() . '/newadmin.yaml');
    }

    public function testRegistrationFormGeneratesACsrfToken(): void
    {
        $this->register(method: 'GET');

        $this->assertInstanceOf(Request::class, $this->panelRequest);
        $this->assertTrue((new CsrfToken($this->panelRequest))->has('panel'));
    }

    /**
     * @param array<string, string> $input
     */
    #[DataProvider('invalidInputProvider')]
    public function testInvalidFormsAreRejectedWithoutCreatingUsers(array $input): void
    {
        $response = $this->register($input);

        $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
        $this->assertFileDoesNotExist($this->accountsPath() . '/newadmin.yaml');
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'empty' => [[]];
        yield 'missing password' => [['fullname' => 'New Admin', 'username' => 'newadmin', 'email' => 'admin@example.test', 'language' => 'en']];
        yield 'short password' => [[...self::validInput(), 'password' => 'short']];
        yield 'invalid username' => [[...self::validInput(), 'username' => '1admin']];
        yield 'short username' => [[...self::validInput(), 'username' => 'ab']];
        yield 'invalid email' => [[...self::validInput(), 'email' => 'not an email']];
    }

    public function testFirstUserIsRegisteredAsAdministratorAndLoggedIn(): void
    {
        $events = new EventDispatcher();
        $loggedIn = [];
        $events->on('panelLoggedIn', function (PanelLoggedInEvent $event) use (&$loggedIn): void {
            $loggedIn[] = $event->user();
        });

        // The user is authenticated by the application, which must find the new user in its own collection
        $users = App::instance()->getService(Users::class);

        try {
            $response = $this->register(self::validInput(), users: $users, services: [EventDispatcher::class => $events]);

            $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
            $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
            $this->assertFileExists($this->accountsPath() . '/newadmin.yaml');
            $this->assertSame('admin', $users->get('newadmin')?->role()->id());
            $this->assertCount(1, $loggedIn);
            $this->assertSame('newadmin', $loggedIn[0]->username());
        } finally {
            $users->remove('newadmin');

            if (FileSystem::exists($this->accountsPath() . '/newadmin.yaml')) {
                FileSystem::delete($this->accountsPath() . '/newadmin.yaml');
            }
        }
    }

    public function testRegistrationFormIsDisplayed(): void
    {
        $this->assertOutcome(['status' => 200], $this->register(method: 'GET'));
    }

    /**
     * @return array<string, string>
     */
    private static function validInput(): array
    {
        return ['fullname' => 'New Admin', 'username' => 'newadmin', 'password' => 'a-long-password', 'email' => 'admin@example.test', 'language' => 'en'];
    }

    private function emptyUsers(): Users
    {
        return new Users([], App::instance()->getService(Users::class)->roles());
    }

    private function accountsPath(): string
    {
        return App::instance()->rootPath() . '/site/users/accounts';
    }

    /**
     * @param array<string, string> $input
     * @param array<string, object> $services
     */
    private function register(array $input = [], string $method = 'POST', bool $usersExist = false, ?Users $users = null, array $services = []): Response
    {
        $app = App::instance();
        $schemes = $app->getService(Schemes::class);

        if (!$schemes->has('forms.register')) {
            $schemes->loadFromPath(TestInstallation::rootPath() . '/panel/schemes');
        }

        $siteUsers = $this->createStub(Users::class);
        $siteUsers->method('isEmpty')->willReturn(!$usersExist);

        $site = $this->createStub(Site::class);
        $site->method('users')->willReturn($siteUsers);

        $controller = $this->makeController(
            RegisterController::class,
            [],
            input: $input,
            services: [Site::class => $site, ...$services],
            server: ['REQUEST_METHOD' => $method],
        );

        return $controller->register($schemes, $app->getService(UserFactory::class), $users ?? $this->emptyUsers());
    }
}
