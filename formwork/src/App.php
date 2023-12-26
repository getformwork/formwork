<?php

namespace Formwork;

use BadMethodCallException;
use Formwork\Cache\AbstractCache;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Images\ImageFactory;
use Formwork\Languages\Languages;
use Formwork\Pages\Site;
use Formwork\Panel\Panel;
use Formwork\Panel\Users\UserFactory;
use Formwork\Parsers\Yaml;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Services\Loaders\ErrorHandlersServiceLoader;
use Formwork\Services\Loaders\LanguagesServiceLoader;
use Formwork\Services\Loaders\PanelServiceLoader;
use Formwork\Services\Loaders\SchemesServiceLoader;
use Formwork\Services\Loaders\SiteServiceLoader;
use Formwork\Services\Loaders\TranslationsServiceLoader;
use Formwork\Traits\SingletonClass;
use Formwork\Translations\Translations;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;

final class App
{
    use SingletonClass;

    /**
     * Current Formwork version
     */
    public const VERSION = '2.0.0-dev';

    protected Container $container;

    /**
     * Create a new Formwork instance
     */
    public function __construct()
    {
        $this->initializeSingleton();

        $this->container = new Container();

        $this->loadServices($this->container);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    public function config(): Config
    {
        return $this->container->get(Config::class);
    }

    public function router(): Router
    {
        return $this->container->get(Router::class);
    }

    public function site(): Site
    {
        return $this->container->get(Site::class);
    }

    public function request(): Request
    {
        return $this->container->get(Request::class);
    }

    public function schemes(): Schemes
    {
        return $this->container->get(Schemes::class);
    }

    public function translations(): Translations
    {
        return $this->container->get(Translations::class);
    }

    public function panel(): ?Panel
    {
        return $this->container->get(Panel::class);
    }

    /**
     * Return default options
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return Yaml::parseFile(SYSTEM_PATH . '/config/system.yaml');
    }

    public function getService(string $name): mixed
    {
        return $this->container->get($name);
    }

    /**
     * Run Formwork
     */
    public function run(): Response
    {
        $this->loadRoutes();

        DynamicFieldValue::$vars = $this->container->call(require $this->config()->get('system.fields.dynamic.vars.file'));

        $response = $this->router()->dispatch();

        $response->send();

        if ($this->config()->get('system.statistics.enabled') && $this->site()->currentPage() !== null && !$this->site()->currentPage()->isErrorPage()) {
            $this->container->get(Statistics::class)->trackVisit();
        }

        return $response;
    }

    protected function loadServices(Container $container): void
    {
        $container->define(Container::class, $container);

        $container->define(static::class, $this);

        $container->define(Config::class)
            ->loader(ConfigServiceLoader::class)
            ->alias('config');

        $container->define(ViewFactory::class)
            ->parameter('methods', fn (Container $container) => $container->call(require SYSTEM_PATH . '/helpers.php'));

        $container->define(Request::class, fn () => Request::fromGlobals())
            ->alias('request');

        $container->define(ErrorHandlers::class)
            ->loader(ErrorHandlersServiceLoader::class)
            ->lazy(!$this->config()->get('system.errors.setHandlers', true));

        $container->define(CsrfToken::class);

        $container->define(Router::class)
            ->alias('router');

        $container->define(Languages::class)
            ->loader(LanguagesServiceLoader::class)
            ->alias('languages');

        $container->define(Translations::class)
            ->loader(TranslationsServiceLoader::class)
            ->alias('translations');

        $container->define(Schemes::class)
            ->loader(SchemesServiceLoader::class)
            ->alias('schemes');

        $container->define(Site::class)
            ->loader(SiteServiceLoader::class)
            ->alias('site');

        $container->define(Statistics::class)
            ->parameter('path', fn (Config $config) => $config->get('system.statistics.path'))
            ->alias('statistics');

        $container->define(FilesCache::class)
            ->parameter('path', fn (Config $config) => $config->get('system.cache.path'))
            ->parameter('defaultTtl', fn (Config $config) => $config->get('system.cache.time'))
            ->alias(AbstractCache::class)
            ->alias('cache');

        $container->define(UserFactory::class);

        $container->define(Panel::class)
            ->loader(PanelServiceLoader::class)
            ->alias('panel');

        $container->define(FileFactory::class)
            ->parameter('associations.image/jpeg', [ImageFactory::class, 'make'])
            ->parameter('associations.image/png', [ImageFactory::class, 'make'])
            ->parameter('associations.image/webp', [ImageFactory::class, 'make'])
            ->parameter('associations.image/gif', [ImageFactory::class, 'make']);

        $container->define(ImageFactory::class);

        $container->define(FileUriGenerator::class);
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        if ($this->config()->get('system.panel.enabled')) {
            $this->router()->loadFromFile(
                $this->config()->get('system.routes.files.panel'),
                Str::wrap($this->config()->get('system.panel.root'), '/')
            );
        }

        $this->router()->loadFromFile($this->config()->get('system.routes.files.system'));
    }
}
