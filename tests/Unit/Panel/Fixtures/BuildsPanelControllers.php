<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Assets\Assets;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Panel;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;

/**
 * Builds panel controllers on top of lightweight collaborators
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait BuildsPanelControllers
{
    private ?Request $panelRequest = null;

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
     * @param array<string, mixed>  $system      Overrides of the `system` configuration
     * @param array<string, mixed>  $input       Request input
     * @param array<string, object> $services    Additional or replacing container services
     *
     * @template T of object
     *
     * @return T
     */
    protected function makeController(string $class, array $permissions, array $system = [], array $input = [], array $services = [], ?User $user = null, ?Users $users = null): object
    {
        $app = App::instance();

        $config = new Config(['system' => array_replace_recursive($app->config()->get('system'), ['panel' => ['root' => '/panel/']], $system)], resolved: true);

        $sessionPath = FileSystem::joinPaths(TESTS_TMP_PATH, 'sessions');
        if (!FileSystem::isDirectory($sessionPath, assertExists: false)) {
            FileSystem::createDirectory($sessionPath, recursive: true);
        }

        $request = new Request($input, [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'REMOTE_ADDR'    => '203.0.113.7',
        ]);
        $request->session()->setPath($sessionPath);
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
        $translations = new Translations($config);
        $translations->loadFromPath(SYSTEM_PATH . '/translations');
        $translations->loadFromPath(ROOT_PATH . '/panel/translations');
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
