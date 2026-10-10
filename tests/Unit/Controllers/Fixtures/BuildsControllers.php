<?php

namespace Formwork\Tests\Unit\Controllers\Fixtures;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Controllers\PageController;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Router\Router;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;

/**
 * Builds site controllers on top of lightweight collaborators
 *
 * @mixin \Formwork\Tests\TestCase
 */
trait BuildsControllers
{
    protected Router $router;

    /**
     * @param class-string<T>       $class
     * @param array<string, mixed>  $system   Overrides of the `system` configuration
     * @param array<string, mixed>  $site     Overrides of the `site` configuration
     * @param array<string, mixed>  $query    Request query
     * @param array<string, mixed>  $server   Overrides of the request server data
     * @param array<string, object> $services Additional or replacing container services
     *
     * @template T of object
     *
     * @return T
     */
    protected function makeController(string $class, array $system = [], array $query = [], array $server = [], array $services = [], ?Router $router = null, array $site = []): object
    {
        $app = App::instance();

        $config = new Config([
            'system' => array_replace_recursive($app->config()->get('system'), $system),
            'site'   => array_replace_recursive($app->config()->get('site') ?? [], $site),
        ], resolved: true);

        $request = new Request([], $query, [], [], $server + [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'REMOTE_ADDR'    => '203.0.113.7',
        ]);

        $this->router = $router ?? $this->createStub(Router::class);

        $container = new ForwardingContainer(replacePageController: $class !== PageController::class);

        foreach ([
            Container::class       => $container,
            App::class             => $app,
            Config::class          => $config,
            Request::class         => $request,
            Router::class          => $this->router,
            EventDispatcher::class => new EventDispatcher(),
        ] as $name => $service) {
            $container->define($name, $services[$name] ?? $service);
            unset($services[$name]);
        }

        foreach ($services as $name => $service) {
            $container->define($name, $service);
        }

        return $container->build($class);
    }

    protected function createDirectory(string $path): string
    {
        if (!FileSystem::isDirectory($path, assertExists: false)) {
            FileSystem::createDirectory($path, recursive: true);
        }
        return $path;
    }
}
