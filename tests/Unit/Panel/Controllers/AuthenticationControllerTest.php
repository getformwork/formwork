<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Events\EventDispatcher;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\AuthenticationController;
use Formwork\Panel\Events\PanelLoggedOutEvent;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
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
        $this->assertStringNotContainsString('panel.index', $response->headers()->get('Location'));
    }

    public function testLogoutWithoutLoggedInUserRedirectsToThePanel(): void
    {
        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn(null);

        $response = $this->makeController(AuthenticationController::class, [], users: $users)->logout();

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

    /**
     * @param array<string, mixed>  $system
     * @param array<string, object> $services
     */
    private function controller(User $user, array $system = [], array $services = []): AuthenticationController
    {
        return $this->makeController(AuthenticationController::class, [], $system, services: $services, user: $user);
    }
}
