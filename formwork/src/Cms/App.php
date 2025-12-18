<?php

namespace Formwork\Cms;

use BadMethodCallException;
use ErrorException;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsController;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Images\ImageFactory;
use Formwork\Languages\LanguagesFactory;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Panel;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Services\Loaders\PanelServiceLoader;
use Formwork\Services\Loaders\SchemesServiceLoader;
use Formwork\Services\Loaders\SiteServiceLoader;
use Formwork\Services\Loaders\TemplatesServiceLoader;
use Formwork\Services\Loaders\TranslationsServiceLoader;
use Formwork\Services\Loaders\UsersServiceLoader;
use Formwork\Statistics\Statistics;
use Formwork\Templates\TemplateFactory;
use Formwork\Templates\Templates;
use Formwork\Traits\SingletonClass;
use Formwork\Translations\Translations;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;
use Throwable;

final class App
{
    use SingletonClass;

    /**
     * Current Formwork version
     */
    public const string VERSION = '2.2.2';

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
     * Get Panel instance
     */
    public function panel(): Panel
    {
        return $this->container->get(Panel::class);
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
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loadErrorHandler();
        $this->loadServices($this->container);
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
                $controller = $this->container->get(ErrorsControllerInterface::class);
                $response = $controller->error(throwable: $throwable);
            } catch (Throwable) {
                ini_restore('display_errors');
                throw $throwable;
            }
        }

        $this->request()->session()->save();

        $response->prepare($this->request())->send();

        return $response;
    }

    /**
     * Define app services
     */
    private function loadServices(Container $container): void
    {
        $container->define(Container::class, $container);

        $container->define(self::class, $this);

        $container->define(Request::class, fn() => Request::fromGlobals())
            ->alias('request');

        $container->define(Config::class)
            ->loader(ConfigServiceLoader::class)
            ->alias('config');

        $container->define(ViewFactory::class)
            ->parameter('methods', fn(Container $container, Config $config) => $container->call(require $config->get('system.views.methods.system')));

        $container->define(ErrorsController::class)
            ->alias(ErrorsControllerInterface::class);

        $container->define(CsrfToken::class)
            ->alias('csrfToken');

        $container->define(Router::class)
            ->alias('router');

        $container->define(Translations::class)
            ->loader(TranslationsServiceLoader::class)
            ->alias('translations');

        $container->define(Schemes::class)
            ->loader(SchemesServiceLoader::class)
            ->alias('schemes');

        $container->define(PageFactory::class);

        $container->define(PaginationFactory::class);

        $container->define(PageCollectionFactory::class);

        $container->define(LanguagesFactory::class);

        $container->define(Site::class)
            ->loader(SiteServiceLoader::class)
            ->alias('site');

        $container->define(TemplateFactory::class);

        $container->define(Templates::class)
            ->loader(TemplatesServiceLoader::class)
            ->alias('templates');

        $container->define(Statistics::class)
            ->parameter('options', fn(Config $config) => $config->get('site.statistics'))
            ->parameter('translation', fn(Translations $translations) => $translations->getCurrent())
            ->alias('statistics');

        $container->define(FilesCache::class)
            ->parameter('path', fn(Config $config) => $config->get('system.cache.path'))
            ->parameter('defaultTtl', fn(Config $config) => $config->get('system.cache.time'))
            ->alias(AbstractCache::class)
            ->alias('cache');

        $container->define(UserFactory::class);

        $container->define(Users::class)
            ->loader(UsersServiceLoader::class)
            ->alias('users');

        $container->define(Panel::class)
            ->loader(PanelServiceLoader::class)
            ->alias('panel');

        $container->define(FileFactory::class)
            ->parameter('associations.image/jpeg', [ImageFactory::class, 'make'])
            ->parameter('associations.image/png', [ImageFactory::class, 'make'])
            ->parameter('associations.image/webp', [ImageFactory::class, 'make'])
            ->parameter('associations.image/gif', [ImageFactory::class, 'make'])
            ->parameter('associations.image/svg+xml', [ImageFactory::class, 'make']);

        $container->define(ImageFactory::class);

        $container->define(FileUriGenerator::class);

        $container->define(FileUploader::class);
    }

    /**
     * Load routes
     */
    private function loadRoutes(): void
    {
        if ($this->config()->get('system.panel.enabled')) {
            $this->router()->loadFromFile(
                $this->config()->get('system.routes.files.panel'),
                Str::wrap($this->config()->get('system.panel.root'), '/')
            );
        }

        $this->router()->loadFromFile($this->config()->get('system.routes.files.system'));
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
