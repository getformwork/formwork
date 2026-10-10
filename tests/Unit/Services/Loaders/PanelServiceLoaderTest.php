<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Log\Logger;
use Formwork\Parsers\Php;
use Formwork\Router\Events\RouteActionResolvedEvent;
use Formwork\Router\Route;
use Formwork\Services\Loaders\PanelServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use UnexpectedValueException;

/**
 * The route gatekeeper of the panel decides who can reach which panel route, so it is exercised on every route the panel defines
 */
#[CoversClass(PanelServiceLoader::class)]
final class PanelServiceLoaderTest extends TestCase
{
    private const string PANEL_ROOT = '/panel/';

    /**
     * Routes reachable by anyone while there are users and nobody is logged in
     */
    private const array PUBLIC_ROUTES = ['panel.login', 'panel.logout', 'panel.assets'];

    /**
     * Routes reachable by anyone from the local machine while there are no users
     */
    private const array SETUP_ROUTES = ['panel.register', 'panel.assets'];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function panelRoutes(): iterable
    {
        /** @var array{routes: array<string, array{path: string}>} $data */
        $data = Php::parseFile(ROOT_PATH . '/panel/config/routes/routes.php');

        foreach ($data['routes'] as $name => $route) {
            yield $name => [$name, $route['path']];
        }
    }

    #[DataProvider('panelRoutes')]
    public function testEveryPanelRouteIsRedirectedAwayFromRemoteClientsWhenThereAreNoUsers(string $name, string $path): void
    {
        $response = $this->gate($name, $path, users: [], localhost: false);

        $this->assertInstanceOf(RedirectResponse::class, $response, sprintf('Route "%s" must not be reachable before the first user exists', $name));
        $this->assertSame((string) parse_url(App::instance()->uri()->path('/'), PHP_URL_PATH), $this->path($response));
    }

    #[DataProvider('panelRoutes')]
    public function testOnlySetupRoutesAreReachableFromLocalhostWhenThereAreNoUsers(string $name, string $path): void
    {
        $response = $this->gate($name, $path, users: [], localhost: true);

        if (in_array($name, self::SETUP_ROUTES, true)) {
            $this->assertNull($response, sprintf('Route "%s" is part of the first user setup', $name));
            return;
        }

        $this->assertInstanceOf(RedirectResponse::class, $response, sprintf('Route "%s" must not be reachable before the first user exists', $name));
        $this->assertStringEndsWith('/register/', $this->path($response));
    }

    #[DataProvider('panelRoutes')]
    public function testOnlyAuthenticationRoutesAreReachableWithoutLoggingIn(string $name, string $path): void
    {
        try {
            $response = $this->gate($name, $path, users: ['alice'], localhost: false);
        } catch (UnexpectedValueException) {
            // Resolving the route to come back to after login needs a real panel request
            $this->assertNotContains($name, self::PUBLIC_ROUTES);
            return;
        }

        if (in_array($name, self::PUBLIC_ROUTES, true)) {
            $this->assertNull($response, sprintf('Route "%s" must be reachable to log in', $name));
            return;
        }

        $this->assertInstanceOf(RedirectResponse::class, $response, sprintf('Route "%s" must require a logged in user', $name));
        $this->assertStringEndsWith('/login/', $this->path($response));
    }

    #[DataProvider('panelRoutes')]
    public function testLoggedInUsersCanReachEveryPanelRoute(string $name, string $path): void
    {
        $this->assertNull($this->gate($name, $path, users: ['alice'], localhost: false, loggedIn: true));
    }

    #[DataProvider('panelRoutes')]
    public function testRoutesOutsideThePanelAreNeverTouched(string $name, string $path): void
    {
        $this->assertNull($this->gate($name, $path, users: [], localhost: false, prefix: '/'));
        $this->assertNull($this->gate($name, $path, users: ['alice'], localhost: false, prefix: '/'));
        $this->assertNull($this->gate($name, $path, users: ['alice'], localhost: false, prefix: '/panel-extra/'));
    }

    public function testLoginIsStillReachableWhenThePanelRootIsConfiguredWithoutSlashes(): void
    {
        $this->assertNull($this->gate('panel.login', '/login/', users: ['alice'], localhost: false, root: 'panel'));
    }

    /**
     * Run the route action resolution listener of the panel
     *
     * @param list<string> $users
     */
    private function gate(string $name, string $path, array $users, bool $localhost, bool $loggedIn = false, string $prefix = self::PANEL_ROOT, string $root = self::PANEL_ROOT): ?RedirectResponse
    {
        $loader = $this->loader($users, $localhost, $loggedIn, $root);

        $route = (new Route($name, $path))->prefix($prefix);
        $original = static fn(): string => 'original action';
        $event = new RouteActionResolvedEvent($route, $original);

        (new ReflectionMethod($loader, 'onRouteActionResolved'))->invoke($loader, $event);

        $action = $event->action();
        if ($action === $original) {
            return null;
        }

        $response = $action();
        $this->assertInstanceOf(RedirectResponse::class, $response);

        return $response;
    }

    /**
     * @param list<string> $usernames
     */
    private function loader(array $usernames, bool $localhost, bool $loggedIn, string $root): PanelServiceLoader
    {
        $app = App::instance();

        $config = new Config(['system' => ['panel' => ['root' => $root]]], resolved: true);

        $request = new Request([], [], [], [], ['REMOTE_ADDR' => $localhost ? '127.0.0.1' : '203.0.113.7', 'REQUEST_METHOD' => 'GET']);

        $users = $this->createStub(Users::class);
        $users->method('isEmpty')->willReturn($usernames === []);
        $users->method('loggedIn')->willReturn($loggedIn ? $this->createStub(User::class) : null);

        return new PanelServiceLoader(
            $app,
            $this->createStub(Logger::class),
            new EventDispatcher(),
            $request,
            $config,
            $app->getService(ViewFactory::class),
            $app->translations(),
            $app->schemes(),
            $users,
            $app->getService(Assets::class),
        );
    }

    private function location(RedirectResponse $response): ?string
    {
        $location = $response->headers()->get('Location');

        return is_string($location) ? $location : null;
    }

    private function path(RedirectResponse $response): string
    {
        return (string) parse_url((string) $this->location($response), PHP_URL_PATH);
    }
}
