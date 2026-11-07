<?php

namespace Formwork\Tests\Unit\Panel;

use Formwork\Assets\Assets;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Files\FileFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\JsonResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Pages\PageFactory;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Panel;
use Formwork\Parsers\Json;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\Fixtures\ControllerContainerFixture;
use Formwork\Tests\Unit\Panel\Fixtures\PanelRequestFixture;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use Formwork\View\View;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\TestCase;

/**
 * Builds panel controllers on top of lightweight collaborators
 *
 * @mixin TestCase
 */
trait BuildsPanelControllers
{
    private ?Request $panelRequest = null;

    private Container $panelContainer;

    /**
     * @param array<string, bool>  $permissions
     * @param array<string, mixed> $methods     Values returned by the methods of the user
     */
    protected function stubUser(array $permissions, array $methods = []): User
    {
        $user = $this->createStub(User::class);
        $user->method('permissions')->willReturn(new Permissions($permissions));

        foreach ($methods as $name => $value) {
            $user->method($name)->willReturn($value);
        }

        return $user;
    }

    /**
     * Call an action of the controller, giving the route parameters (and a stub for any other dependency) it asks for
     *
     * @param array<string, mixed> $params
     */
    protected function call(object $controller, string $action, array $params = []): Response
    {
        /** @var Response */
        return $this->panelContainer->call($controller->{$action}(...), ['routeParams' => new RouteParams($params)]);
    }

    /**
     * Describe a response: `status`, `to` (name of the route redirected to), `json` (decoded content) and `notices` (types of the notifications flashed to the session)
     *
     * @return array<string, mixed>
     */
    protected function outcome(Response $response): array
    {
        $outcome = ['status' => $response->status()->code()];

        if ($response instanceof RedirectResponse) {
            $outcome['to'] = substr($response->headers()->get('Location'), strpos($response->headers()->get('Location'), '/route/') + strlen('/route/'));
        } elseif ($response instanceof JsonResponse) {
            $outcome['json'] = Json::parse($response->content());
        }

        $outcome['notices'] = [];

        foreach ($this->panelRequest?->session()->messages()->getAll() ?? [] as $type => $messages) {
            array_push($outcome['notices'], ...array_fill(0, count($messages), $type));
        }

        return $outcome;
    }

    /**
     * Assert that a response matches the expected outcome, which lists only the keys of interest (arrays are compared by subset)
     *
     * @param array<string, mixed> $expected
     */
    protected function assertOutcome(array $expected, Response $response): void
    {
        $outcome = $this->outcome($response);

        $this->assertSame($expected, $expected === [] ? [] : $this->restrict($outcome, $expected));
    }

    /**
     * Close the session opened by the controllers (PHP supports only one active session per process)
     */
    protected function closePanelSession(): void
    {
        if ($this->panelRequest?->session()->isStarted()) {
            $this->panelRequest->session()->save();
        }
        $this->panelRequest = null;
    }

    /**
     * @param class-string<T>       $class
     * @param array<string, bool>   $permissions
     * @param array<string, mixed>  $system               Overrides of the `system` configuration
     * @param array<string, mixed>  $input                Request input
     * @param array<string, string> $query                Request query
     * @param array<string, string> $server               Additional or replacing server variables
     * @param array<string, mixed>  $files                Uploaded files of the request
     * @param bool                  $previousSession      Whether the request has a previous session
     * @param bool                  $realErrorsController Whether to build the real errors controller when a controller forwards to it
     * @param array<string, object> $services             Additional or replacing container services
     *
     * @template T of object
     *
     * @return T
     */
    protected function makeController(string $class, array $permissions = [], array $system = [], array $input = [], array $services = [], ?User $user = null, ?Users $users = null, array $query = [], bool $realErrorsController = false, array $server = [], bool $previousSession = false, array $files = []): object
    {
        $app = App::instance();

        $config = new Config(['system' => array_replace_recursive($app->config()->get('system'), ['panel' => ['root' => '/panel/']], $system)], resolved: true);

        $request = $this->panelRequest($input, $query, $server, $files, $previousSession);

        $user ??= $this->stubUser($permissions);

        if ($users === null) {
            $users = $this->createStub(Users::class);
            $users->method('loggedIn')->willReturn($user);
        }

        $this->panelContainer = $container = new ControllerContainerFixture();
        $container->realErrorsController = $realErrorsController;

        foreach ([
            Container::class       => $container,
            App::class             => $app,
            Config::class          => $config,
            Request::class         => $request,
            Router::class          => $this->panelRouter(),
            EventDispatcher::class => new EventDispatcher(),
            Translations::class    => $this->panelTranslations($config),
            Site::class            => $this->createStub(Site::class),
            Users::class           => $users,
            Modals::class          => $this->createStub(Modals::class),
            Assets::class          => $this->createStub(Assets::class),
            FileUploader::class    => $this->createStub(FileUploader::class),
            FileFactory::class     => $app->getService(FileFactory::class),
            PageFactory::class     => $app->getService(PageFactory::class),
            ViewFactory::class     => $this->emptyViewFactory(),
        ] as $name => $service) {
            $container->define($name, $services[$name] ?? $service);
            unset($services[$name]);
        }

        foreach ($services as $name => $service) {
            $container->define($name, $service);
        }

        $container->define(CsrfToken::class);
        $container->define(Panel::class);

        return $container->build($class);
    }

    /**
     * Create the request of the panel, keeping it to close its session later
     *
     * @param array<string, mixed>  $input
     * @param array<string, string> $query
     * @param array<string, string> $server
     * @param array<string, mixed>  $files
     */
    private function panelRequest(array $input, array $query, array $server, array $files, bool $previousSession): PanelRequestFixture
    {
        $sessionPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'sessions');

        if (!FileSystem::isDirectory($sessionPath, assertExists: false)) {
            FileSystem::createDirectory($sessionPath, recursive: true);
        }

        $request = new PanelRequestFixture($input, $query, server: [
            'REQUEST_METHOD' => 'POST',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'REMOTE_ADDR'    => '203.0.113.7',
            ...$server,
        ]);
        $request->session()->setPath($sessionPath);
        $request->previousSession = $previousSession;
        $request->withFiles($files);

        $this->closePanelSession();

        return $this->panelRequest = $request;
    }

    /**
     * Create a router generating `/route/<name>` URIs
     */
    private function panelRouter(): Router
    {
        $router = $this->createStub(Router::class);
        $router->method('generate')->willReturnCallback(static fn(string $name, array $params = []): string => '/route/' . $name);

        return $router;
    }

    private function panelTranslations(Config $config): Translations
    {
        $translations = new Translations($config);
        $translations->loadFromPath(TestInstallation::systemPath() . '/translations');
        $translations->loadFromPath(TestInstallation::rootPath() . '/panel/translations');
        $translations->setCurrent('en');

        return $translations;
    }

    /**
     * Keep of the actual value only what the expectation describes
     */
    private function restrict(mixed $actual, mixed $expected): mixed
    {
        if (!is_array($actual) || !is_array($expected)) {
            return $actual;
        }

        $restricted = [];

        foreach (array_is_list($expected) ? array_keys($actual) : array_keys($expected) as $key) {
            if (array_key_exists($key, $actual)) {
                $restricted[$key] = $this->restrict($actual[$key], $expected[$key] ?? null);
            }
        }

        return $restricted;
    }

    /**
     * Create a view factory whose views render nothing, so that controllers can be tested without any markup
     */
    private function emptyViewFactory(): ViewFactory
    {
        $factory = $this->createStub(ViewFactory::class);
        $factory->method('make')->willReturn($this->createStub(View::class));

        return $factory;
    }
}
