<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Assets\Assets;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Panel;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Tests\TestInstallation;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
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

    /**
     * Decode a response rendered with the fake views
     *
     * @return array{view: string, vars: array<string, mixed>}
     */
    protected function renderedView(Response $response): array
    {
        return FakeViews::decode($response->content());
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
     * @param bool                  $previousSession      Whether the request has a previous session
     * @param bool                  $realErrorsController Whether to build the real errors controller when a controller forwards to it
     * @param array<string, object> $services             Additional or replacing container services
     *
     * @template T of object
     *
     * @return T
     */
    protected function makeController(string $class, array $permissions, array $system = [], array $input = [], array $services = [], ?User $user = null, ?Users $users = null, array $query = [], bool $realErrorsController = false, array $server = [], bool $previousSession = false): object
    {
        $app = App::instance();

        $config = new Config(['system' => array_replace_recursive($app->config()->get('system'), ['panel' => ['root' => '/panel/']], $system)], resolved: true);

        $sessionPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'sessions');
        if (!FileSystem::isDirectory($sessionPath, assertExists: false)) {
            FileSystem::createDirectory($sessionPath, recursive: true);
        }

        $request = new PanelRequest($input, $query, [], [], [
            'REQUEST_METHOD' => 'POST',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'REMOTE_ADDR'    => '203.0.113.7',
            ...$server,
        ]);
        $request->session()->setPath($sessionPath);
        $request->previousSession = $previousSession;
        $this->closePanelSession();
        $this->panelRequest = $request;

        if ($user === null) {
            $user = $this->createStub(User::class);
            $user->method('permissions')->willReturn(new Permissions($permissions));
        }

        if ($users === null) {
            $users = $this->createStub(Users::class);
            $users->method('loggedIn')->willReturn($user);
        }

        $router = $this->createStub(Router::class);
        $router->method('generate')->willReturnCallback(static fn(string $name, array $params = []): string => '/route/' . $name);

        $container = new ControllerContainer();
        $container->realErrorsController = $realErrorsController;
        $translations = new Translations($config);
        $translations->loadFromPath(TestInstallation::systemPath() . '/translations');
        $translations->loadFromPath(TestInstallation::rootPath() . '/panel/translations');
        $translations->setCurrent('en');

        foreach ([
            Container::class       => $container,
            App::class             => $app,
            Config::class          => $config,
            Request::class         => $request,
            Router::class          => $router,
            EventDispatcher::class => new EventDispatcher(),
            Translations::class    => $translations,
            Site::class            => $this->createStub(Site::class),
            Users::class           => $users,
            Modals::class          => $this->createStub(Modals::class),
            Assets::class          => $this->createStub(Assets::class),
            FileUploader::class    => $this->createStub(FileUploader::class),
            ViewFactory::class     => FakeViews::factory(FileSystem::joinPaths(TESTS_TMP_PATH, 'views')),
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
}
