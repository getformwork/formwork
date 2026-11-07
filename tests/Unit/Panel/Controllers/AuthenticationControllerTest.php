<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\Exceptions\AuthenticationFailedException;
use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Cms\App;
use Formwork\Events\EventDispatcher;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\AuthenticationController;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Panel\Events\PanelLoggedOutEvent;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\User;
use Formwork\Users\Users;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AuthenticationController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class AuthenticationControllerTest extends TestCase
{
    use BuildsPanelControllers;

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testLoggedInUsersAreRedirectedToThePanel(): void
    {
        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn($this->createStub(User::class));

        $response = $this->login(method: 'GET', users: $users, previousSession: true);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testInvalidFormsAreReportedWithoutAuthenticating(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->expects($this->never())->method('login');

        $this->login(['login' => '', 'password' => ''], authenticator: $authenticator);

        $this->assertSame('error', $this->firstNotificationType());
    }

    public function testCredentialsAreVerifiedByTheAuthenticator(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->expects($this->once())->method('login')->with('admin', 'secret')->willReturn($this->createStub(User::class));

        $response = $this->login(['login' => 'admin', 'password' => 'secret'], authenticator: $authenticator);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testLoginDispatchesAnEvent(): void
    {
        $user = $this->createStub(User::class);
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('login')->willReturn($user);
        $events = new EventDispatcher();
        $dispatched = [];
        $events->on('panelLoggedIn', function (PanelLoggedInEvent $event) use (&$dispatched): void {
            $dispatched[] = $event->user();
        });

        $this->login(['login' => 'admin', 'password' => 'secret'], authenticator: $authenticator, services: [EventDispatcher::class => $events]);

        $this->assertSame([$user], $dispatched);
    }

    public function testLoginRedirectsToTheRequestedPageOnce(): void
    {
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('login')->willReturn($this->createStub(User::class));

        $controller = $this->makeController(AuthenticationController::class, input: ['login' => 'admin', 'password' => 'secret']);
        $this->panelRequest?->session()->set(AuthenticationController::SESSION_REDIRECT_KEY, '/pages/');

        $response = $controller->login($this->schemes(), $authenticator);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/panel/pages/', $response->headers()->get('Location'));
        $this->assertFalse($this->panelRequest?->session()->has(AuthenticationController::SESSION_REDIRECT_KEY));
    }

    public function testFailedAuthenticationIsReported(): void
    {
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('login')->willThrowException(new AuthenticationFailedException('Failed'));

        $response = $this->login(['login' => 'admin', 'password' => 'wrong'], authenticator: $authenticator);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('error', $this->firstNotificationType());
    }

    public function testRateLimitedLoginsGetAStatusAndARetryHeader(): void
    {
        $authenticator = $this->createStub(Authenticator::class);
        $authenticator->method('login')->willThrowException(new RateLimitExceededException('Too many', 600));

        $response = $this->login(['login' => 'admin', 'password' => 'secret'], authenticator: $authenticator);

        $this->assertSame(ResponseStatus::TooManyRequests, $response->status());
        $this->assertSame('600', $response->headers()->get('Retry-After'));
        $this->assertSame('error', $this->firstNotificationType());
    }

    public function testLogoutLogsTheUserOutAndRedirectsToThePanel(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('logout');

        $response = $this->controller($user)->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(ResponseStatus::Found, $response->status());
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testLogoutNotifiesTheUser(): void
    {
        $controller = $this->controller($this->createStub(User::class));

        $controller->logout();

        $notifications = $this->panelRequest?->session()->messages()->getAll() ?? [];

        $this->assertSame(['info'], array_keys($notifications));
    }

    public function testLogoutDispatchesAnEvent(): void
    {
        $user = $this->createStub(User::class);
        $events = new EventDispatcher();
        $dispatched = [];
        $events->on('panelLoggedOut', function (PanelLoggedOutEvent $event) use (&$dispatched): void {
            $dispatched[] = $event->user();
        });

        $this->controller($user, services: [EventDispatcher::class => $events])->logout();

        $this->assertSame([$user], $dispatched);
    }

    public function testLogoutCanRedirectToTheHomePage(): void
    {
        $response = $this->controller($this->createStub(User::class), ['panel' => ['logoutRedirect' => 'home']])->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/', $response->headers()->get('Location'));
        $this->assertStringNotContainsString('panel.index', (string) $response->headers()->get('Location'));
    }

    public function testLogoutWithoutLoggedInUserRedirectsToThePanel(): void
    {
        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn(null);

        $response = $this->makeController(AuthenticationController::class, users: $users)->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testLogoutOfAUserThatIsNotLoggedInIsIgnored(): void
    {
        $user = $this->createStub(User::class);
        $user->method('logout')->willThrowException(new UserNotLoggedException('Not logged'));

        $response = $this->controller($user)->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.index', $response->headers()->get('Location'));
    }

    public function testLogoutDoesNotDispatchAnEventWhenTheUserIsNotLoggedIn(): void
    {
        $user = $this->createStub(User::class);
        $user->method('logout')->willThrowException(new UserNotLoggedException('Not logged'));
        $events = new EventDispatcher();
        $dispatched = 0;
        $events->on('panelLoggedOut', function () use (&$dispatched): void {
            ++$dispatched;
        });

        $this->controller($user, services: [EventDispatcher::class => $events])->logout();

        $this->assertSame(0, $dispatched);
    }

    public function testLoginFormIsDisplayed(): void
    {
        $this->assertOutcome(['status' => 200, 'notices' => []], $this->login(method: 'GET'));
    }

    /**
     * @param array<string, mixed>  $system
     * @param array<string, object> $services
     */
    private function controller(User $user, array $system = [], array $services = []): AuthenticationController
    {
        return $this->makeController(AuthenticationController::class, system: $system, services: $services, user: $user);
    }

    private function schemes(): Schemes
    {
        $schemes = App::instance()->getService(Schemes::class);

        if (!$schemes->has('forms.login')) {
            $schemes->loadFromPath(TestInstallation::rootPath() . '/panel/schemes');
        }

        return $schemes;
    }

    /**
     * @param array<string, string> $input
     * @param array<string, object> $services
     */
    private function login(array $input = [], string $method = 'POST', ?Authenticator $authenticator = null, ?Users $users = null, bool $previousSession = false, array $services = []): Response
    {
        $controller = $this->makeController(
            AuthenticationController::class,
            [],
            input: $input,
            services: $services,
            users: $users,
            server: ['REQUEST_METHOD' => $method],
            previousSession: $previousSession,
        );

        return $controller->login($this->schemes(), $authenticator ?? $this->createStub(Authenticator::class));
    }

    private function firstNotificationType(): ?string
    {
        $messages = $this->panelRequest?->session()->messages()->getAll() ?? [];

        return array_key_first($messages);
    }
}
