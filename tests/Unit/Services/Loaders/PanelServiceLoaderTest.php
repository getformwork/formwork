<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Assets\Assets;
use Formwork\Authentication\RateLimiter;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Fields\FieldFactory;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Session\Session;
use Formwork\Log\Logger;
use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Panel\Panel;
use Formwork\Parsers\Php;
use Formwork\Router\Events\RouteActionResolvedEvent;
use Formwork\Router\Route;
use Formwork\Services\Container;
use Formwork\Services\Loaders\PanelServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\Fixtures\PanelRequestFixture;
use Formwork\Tests\UsesApplication;
use Formwork\Translations\Translations;
use Formwork\Users\Attributes\Permission;
use Formwork\Users\User;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;

/**
 * The route gatekeeper of the panel decides who can reach which panel route, so it is exercised on every route the panel defines
 */
#[CoversClass(PanelServiceLoader::class)]
#[UsesApplication]
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
     * Routes whose action requires no permission: they are the ones to log in or set up the panel, serve public files or
     * errors, or check the permission themselves
     */
    private const array ACTIONS_WITHOUT_PERMISSION = ['panel.login', 'panel.logout', 'panel.register', 'panel.assets', 'panel.errors.notFound', 'panel.dashboard'];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function panelRoutesProvider(): iterable
    {
        /** @var array{routes: array<string, array{path: string}>} $data */
        $data = Php::parseFile(TestInstallation::rootPath() . '/panel/config/routes/routes.php');

        foreach ($data['routes'] as $name => $route) {
            yield $name => [$name, $route['path']];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function panelActionProvider(): iterable
    {
        /** @var array{routes: array<string, array{action?: mixed}>} $data */
        $data = Php::parseFile(TestInstallation::rootPath() . '/panel/config/routes/routes.php');

        foreach ($data['routes'] as $name => $route) {
            if (is_string($route['action'] ?? null) && str_contains($route['action'], '@')) {
                yield $name => [$name, $route['action']];
            }
        }
    }

    #[DataProvider('panelActionProvider')]
    public function testEveryPanelActionDeclaresItsPermission(string $name, string $action): void
    {
        [$class, $method] = explode('@', $action);

        $attributes = (new ReflectionMethod($class, $method))->getAttributes(Permission::class);

        if (in_array($name, self::ACTIONS_WITHOUT_PERMISSION, true)) {
            $this->assertSame([], $attributes, sprintf('Route "%s" is listed as not requiring a permission', $name));
            return;
        }

        $this->assertCount(1, $attributes, sprintf('The action of route "%s" must declare the permission it requires', $name));
    }

    #[DataProvider('panelRoutesProvider')]
    public function testEveryPanelRouteIsRedirectedAwayFromRemoteClientsWhenThereAreNoUsers(string $name, string $path): void
    {
        $response = $this->gate($name, $path, users: [], localhost: false);

        $this->assertInstanceOf(RedirectResponse::class, $response, sprintf('Route "%s" must not be reachable before the first user exists', $name));
        $this->assertSame((string) parse_url(App::instance()->uri()->path('/'), PHP_URL_PATH), $this->path($response));
    }

    #[DataProvider('panelRoutesProvider')]
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

    #[DataProvider('panelRoutesProvider')]
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

    #[DataProvider('panelRoutesProvider')]
    public function testLoggedInUsersCanReachEveryPanelRoute(string $name, string $path): void
    {
        $this->assertNull($this->gate($name, $path, users: ['alice'], localhost: false, loggedIn: true));
    }

    #[DataProvider('panelRoutesProvider')]
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

    public function testDeprecatedPanelOptionsAreStillApplied(): void
    {
        $request = new Request(server: ['REMOTE_ADDR' => '203.0.113.7', 'REQUEST_METHOD' => 'GET']);
        $container = $this->panelContainer($request, $this->createStub(Users::class));
        $config = $this->panelConfig(['loginAttempts' => 3, 'loginResetTime' => 60, 'sessionTimeout' => 30]);

        $messages = $this->captureDeprecations(fn() => $this->panelLoader($config, $request, $this->createStub(Users::class))->load($container));

        $this->assertCount(3, $messages);

        $rateLimiter = $container->get(RateLimiter::class);
        $this->assertInstanceOf(RateLimiter::class, $rateLimiter);
        $this->assertSame(3, (new ReflectionProperty($rateLimiter, 'limit'))->getValue($rateLimiter));
        $this->assertSame(60, (new ReflectionProperty($rateLimiter, 'resetTime'))->getValue($rateLimiter));
        // The session timeout was given in minutes
        $this->assertSame(1800, (new ReflectionProperty(Session::class, 'duration'))->getValue($request->session()));
    }

    public function testPanelOfLoggedInUsersUsesTheirLanguageAndErrorPages(): void
    {
        $user = $this->user('it');
        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn($user);

        // Users are logged in only with a previous session
        $request = new PanelRequestFixture(server: ['REMOTE_ADDR' => '203.0.113.7', 'REQUEST_METHOD' => 'GET']);
        $request->previousSession = true;
        $container = $this->panelContainer($request, $users);
        $loader = $this->panelLoader($this->panelConfig(), $request, $users);
        $translations = App::instance()->translations();
        $current = $translations->getCurrent()->code();

        try {
            $panel = $loader->load($container);
            $container->define(Panel::class, $panel);
            $loader->onResolved($panel, $container);

            $this->assertSame('it', $translations->getCurrent()->code());
            // The errors of the panel are shown by its own controller
            $this->assertSame(ErrorsController::class, (new ReflectionMethod($container, 'resolveAlias'))->invoke($container, ErrorsControllerInterface::class));
        } finally {
            $translations->setCurrent($current);
        }
    }

    public function testLoginsAreLogged(): void
    {
        $user = $this->user('en');
        $request = new Request(server: ['REMOTE_ADDR' => '203.0.113.7', 'REQUEST_METHOD' => 'GET']);
        $eventDispatcher = new EventDispatcher();

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())->method('info')->with('Panel user {username} logged in', ['username' => 'alice']);

        $this->panelLoader($this->panelConfig(), $request, $this->createStub(Users::class), $logger, $eventDispatcher)->load($this->panelContainer($request, $this->createStub(Users::class)));

        $eventDispatcher->dispatch(new PanelLoggedInEvent($user, $request));
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
        $routeActionResolvedEvent = new RouteActionResolvedEvent($route, $original);

        (new ReflectionMethod($loader, 'onRouteActionResolved'))->invoke($loader, $routeActionResolvedEvent);

        $action = $routeActionResolvedEvent->action();
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

        $request = new Request(server: ['REMOTE_ADDR' => $localhost ? '127.0.0.1' : '203.0.113.7', 'REQUEST_METHOD' => 'GET']);

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

    /**
     * @param array<string, mixed> $panel Overrides of the panel configuration
     */
    private function panelConfig(array $panel = []): Config
    {
        return new Config(['system' => array_replace_recursive(App::instance()->config()->getArray('system'), ['panel' => $panel])], resolved: true);
    }

    private function panelLoader(Config $config, Request $request, Users $users, ?Logger $logger = null, ?EventDispatcher $eventDispatcher = null): PanelServiceLoader
    {
        $app = App::instance();

        return new PanelServiceLoader(
            $app,
            $logger ?? $this->createStub(Logger::class),
            $eventDispatcher ?? new EventDispatcher(),
            $request,
            $config,
            $app->getService(ViewFactory::class),
            $app->translations(),
            $app->schemes(),
            $users,
            $app->getService(Assets::class),
        );
    }

    private function user(string $language): User
    {
        return App::instance()->getService(UserFactory::class)->make([
            'username' => 'alice',
            'fullname' => 'Alice',
            'email'    => 'alice@example.test',
            'role'     => 'admin',
            'language' => $language,
        ]);
    }

    /**
     * Create a container with the services needed to build the panel
     */
    private function panelContainer(Request $request, Users $users): Container
    {
        $app = App::instance();
        $container = new Container();

        foreach ([
            Container::class       => $container,
            App::class             => $app,
            Config::class          => $app->config(),
            Request::class         => $request,
            Users::class           => $users,
            Translations::class    => $app->translations(),
            Assets::class          => $app->getService(Assets::class),
            EventDispatcher::class => new EventDispatcher(),
            FieldFactory::class    => $app->getService(FieldFactory::class),
            Site::class            => $app->site(),
        ] as $name => $service) {
            $container->define($name, $service);
        }

        return $container;
    }

    private function location(RedirectResponse $redirectResponse): ?string
    {
        $location = $redirectResponse->headers()->get('Location');

        return is_string($location) ? $location : null;
    }

    private function path(RedirectResponse $redirectResponse): string
    {
        return (string) parse_url((string) $this->location($redirectResponse), PHP_URL_PATH);
    }
}
