<?php

use Formwork\Assets\Assets;
use Formwork\Authentication\Authenticator;
use Formwork\Backup\Backupper;
use Formwork\Cache\CacheManager;
use Formwork\Cms\Site;
use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Controllers\ErrorsController;
use Formwork\Controllers\ErrorsControllerInterface;
use Formwork\Events\EventDispatcher;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\Request;
use Formwork\Http\Session\Session;
use Formwork\Images\ImageFactory;
use Formwork\Languages\Languages;
use Formwork\Languages\LanguagesFactory;
use Formwork\Log\Logger;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Panel;
use Formwork\Plugins\Plugins;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Services\Loaders\AssetsServiceLoader;
use Formwork\Services\Loaders\AuthenticationServiceLoader;
use Formwork\Services\Loaders\CacheServiceLoader;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Services\Loaders\LanguagesServiceLoader;
use Formwork\Services\Loaders\LoggerServiceLoader;
use Formwork\Services\Loaders\PanelServiceLoader;
use Formwork\Services\Loaders\PluginsServiceLoader;
use Formwork\Services\Loaders\SchemesServiceLoader;
use Formwork\Services\Loaders\SiteServiceLoader;
use Formwork\Services\Loaders\TemplatesServiceLoader;
use Formwork\Services\Loaders\TranslationsServiceLoader;
use Formwork\Services\Loaders\UsersServiceLoader;
use Formwork\Statistics\Statistics;
use Formwork\Templates\TemplateFactory;
use Formwork\Templates\Templates;
use Formwork\Translations\Translations;
use Formwork\Updater\Updater;
use Formwork\Users\UserFactory;
use Formwork\Users\Users;
use Formwork\View\ViewFactory;
use Psr\Log\LoggerInterface;

return function (Container $container) {
    $container->define(Logger::class)
        ->loader(LoggerServiceLoader::class)
        ->alias(LoggerInterface::class);

    $container->define(EventDispatcher::class)
        ->alias('events');

    $container->define(Request::class, fn() => Request::fromGlobals())
        ->alias('request');

    $container->define(Session::class, fn(Request $request) => $request->session());

    $container->define(Config::class)
        ->loader(ConfigServiceLoader::class)
        ->alias('config')
        ->lazy(false);

    $container->define(ViewFactory::class)
        ->parameter('resolutionPaths', fn(Config $config) => ['system' => $config->getString('system.views.paths.system')])
        ->parameter('methods', fn(Container $container, Config $config) => $container->call(require $config->getString('system.views.methods.system')));

    $container->define(ErrorsController::class)
        ->alias(ErrorsControllerInterface::class);

    $container->define(CsrfToken::class)
        ->alias('csrfToken');

    $container->define(Router::class)
        ->alias('router');

    $container->define(UriGenerator::class)
        ->alias('uri');

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

    $container->define(Languages::class)
        ->loader(LanguagesServiceLoader::class);

    $container->define(Site::class)
        ->loader(SiteServiceLoader::class)
        ->alias('site');

    $container->define(TemplateFactory::class);

    $container->define(Templates::class)
        ->loader(TemplatesServiceLoader::class)
        ->alias('templates');

    $container->define(Statistics::class)
        ->parameter('options', fn(Config $config) => $config->getArray('site.statistics'))
        ->parameter('translation', fn(Translations $translations) => $translations->getCurrent())
        ->alias('statistics');

    $container->define(CacheManager::class)
        ->alias('cache');

    $container->define('cache.pages')
        ->parameter('namespace', 'pages')
        ->loader(CacheServiceLoader::class);

    $container->define(UserFactory::class);

    $container->define(Users::class)
        ->loader(UsersServiceLoader::class)
        ->alias('users');

    $container->define(Authenticator::class)
        ->loader(AuthenticationServiceLoader::class);

    $container->define(Assets::class)
        ->loader(AssetsServiceLoader::class)
        ->alias('assets');

    $container->define(Panel::class)
        ->loader(PanelServiceLoader::class)
        ->alias('panel');

    $container->define(FileFactory::class)
        ->parameter('associations.image/jpeg', [ImageFactory::class, 'make'])
        ->parameter('associations.image/png', [ImageFactory::class, 'make'])
        ->parameter('associations.image/gif', [ImageFactory::class, 'make'])
        ->parameter('associations.image/webp', [ImageFactory::class, 'make'])
        ->parameter('associations.image/avif', [ImageFactory::class, 'make'])
        ->parameter('associations.image/svg+xml', [ImageFactory::class, 'make']);

    $container->define(ImageFactory::class);

    $container->define(FileUriGenerator::class);

    $container->define(FileUploader::class);

    $container->define(Plugins::class)
        ->loader(PluginsServiceLoader::class)
        ->alias('plugins');

    $container->define(Backupper::class)
        ->parameter('options', fn(Config $config) => $config->getArray('system.backup'));

    $container->define(Updater::class)
        ->parameter('options', fn(Config $config) => $config->getArray('system.updates'));
};
