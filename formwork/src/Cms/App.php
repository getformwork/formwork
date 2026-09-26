<?php

namespace Formwork\Cms;

use BadMethodCallException;
use ErrorException;
use Formwork\Assets\Assets;
use Formwork\Cms\Events\ExceptionThrownEvent;
use Formwork\Cms\Events\ResponseBeforeSendEvent;
use Formwork\Cms\Events\RoutesAfterLoadEvent;
use Formwork\Cms\Events\RoutesBeforeLoadEvent;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Panel\Panel;
use Formwork\Plugins\Plugins;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Traits\SingletonClass;
use Formwork\Translations\Translations;
use Formwork\Utils\Str;
use Throwable;

final class App
{
    use SingletonClass;

    /**
     * Current Formwork version
     */
    public const string VERSION = '2.3.12';

    /**
     * App services container
     */
    private Container $container;

    /**
     * Whether the app has been loaded
     */
    private bool $loaded = false;

    public function __construct()
    {
        $this->initializeSingleton();

        $this->container = new Container();
    }

    /**
     * @param list<mixed> $arguments
     *
     * @throws BadMethodCallException If the called method is not defined
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', self::class, $name));
    }

    /**
     * Get Config instance
     */
    public function config(): Config
    {
        return $this->container->get(Config::class);
    }

    /**
     * Get Router instance
     */
    public function router(): Router
    {
        return $this->container->get(Router::class);
    }

    /**
     * Get UriGenerator instance
     */
    public function uri(): UriGenerator
    {
        return $this->container->get(UriGenerator::class);
    }

    /**
     * Get Site instance
     */
    public function site(): Site
    {
        return $this->container->get(Site::class);
    }

    /**
     * Get Request instance
     */
    public function request(): Request
    {
        return $this->container->get(Request::class);
    }

    /**
     * Get Schemes instance
     */
    public function schemes(): Schemes
    {
        return $this->container->get(Schemes::class);
    }

    /**
     * Get Translations instance
     */
    public function translations(): Translations
    {
        return $this->container->get(Translations::class);
    }

    /**
     * Get Assets instance
     *
     * @since 2.3.0
     */
    public function assets(): Assets
    {
        return $this->container->get(Assets::class);
    }

    /**
     * Get Panel instance
     */
    public function panel(): Panel
    {
        return $this->container->get(Panel::class);
    }

    /**
     * Get EventDispatcher instance
     *
     * @since 2.3.0
     */
    public function events(): EventDispatcher
    {
        return $this->container->get(EventDispatcher::class);
    }

    /**
     * Get Plugins instance
     *
     * @since 2.3.0
     */
    public function plugins(): Plugins
    {
        return $this->container->get(Plugins::class);
    }

    /**
     * Check if a service is defined in the container
     *
     * @since 2.3.6
     *
     * @param class-string<T>|string $name
     */
    public function hasService(string $name): bool
    {
        return $this->container->has($name);
    }

    /**
     * Get a service from the container
     *
     * @template T of object
     *
     * @param class-string<T>|string $name
     *
     * @return ($name is class-string<T> ? T : object)
     */
    public function getService(string $name): object
    {
        return $this->container->get($name);
    }

    /**
     * Load Formwork app
     *
     * @since 2.1.0
     *
     * @internal
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loadErrorHandler();
        $this->loadServices($this->container);
        $this->plugins()->initializeEnabled();
        $this->loadRoutes();
        $this->loaded = true;
    }

    /**
     * Run Formwork
     */
    public function run(): Response
    {
        try {
            $this->load();
            $response = $this->router()->dispatch();
        } catch (Throwable $throwable) {
            try {
                $this->events()->dispatch(new ExceptionThrownEvent($throwable, $this->request()));
                $controller = $this->container->get(ErrorsControllerInterface::class);
                $response = $controller->error(throwable: $throwable);
            } catch (Throwable) {
                // If an exception is thrown while handling the error, rethrow the original exception
                throw $throwable;
            }
        }

        $this->request()->session()->save();

        $response->prepare($this->request());

        $this->events()->dispatch(new ResponseBeforeSendEvent($response, $this->request()));

        $response->send();

        return $response;
    }

    /**
     * Define app services
     */
    private function loadServices(Container $container): void
    {
        $container->define(Container::class, $container)
            ->lazy(false);

        $container->define(self::class, $this)
            ->lazy(false);

        $definitions = require SYSTEM_PATH . '/config/services/services.php';
        $definitions($container);
    }

    /**
     * Load routes
     */
    private function loadRoutes(): void
    {
        $this->events()->dispatch(new RoutesBeforeLoadEvent($this->router()));

        if ($this->config()->getBool('system.panel.enabled')) {
            $this->router()->loadFromFile(
                $this->config()->getString('system.routes.files.panel'),
                Str::wrap($this->config()->getString('system.panel.root'), '/')
            );
        }

        $this->router()->loadFromFile($this->config()->getString('system.routes.files.system'));

        $this->events()->dispatch(new RoutesAfterLoadEvent($this->router()));
    }

    /**
     * Load error handler
     *
     * @throws ErrorException When an error occurs that should be converted to an exception
     */
    private function loadErrorHandler(): void
    {
        ini_set('display_errors', 0);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity) || $severity === E_USER_DEPRECATED) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
