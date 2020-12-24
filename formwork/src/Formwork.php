<?php

namespace Formwork;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Cache\SiteCache;
use Formwork\Languages\Languages;
use Formwork\Parsers\PHP;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Traits\SingletonTrait;
use Formwork\Translations\Translations;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

final class Formwork
{
    use SingletonTrait;

    /**
     * Current Formwork version
     *
     * @var string
     */
    public const VERSION = '1.11.0';

    /**
     * Formwork config
     *
     * @var Config
     */
    protected $config = [];

    /**
     * Current request URI
     *
     * @var string
     */
    protected $request;

    /**
     * Languages instance
     *
     * @var Languages
     */
    protected $languages;

    /**
     * @var Translations
     */
    protected $translations;

    /**
     * Site instance
     *
     * @var Site
     */
    protected $site;

    /**
     * Router instance
     *
     * @var Router
     */
    protected $router;

    /**
     * Cache instance
     *
     * @var SiteCache
     */
    protected $cache;

    /**
     * Create a new Formwork instance
     */
    public function __construct()
    {
        $this->initializeSingleton();

        Errors::setHandlers();

        $this->request = Uri::removeQuery(HTTPRequest::uri());

        $this->loadConfig();
        $this->loadLanguages();
        $this->loadTranslations();
        $this->loadSite();
        $this->loadCache();
        $this->loadRoutes();
    }

    /**
     * Get system options
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Get current request
     */
    public function request(): string
    {
        return $this->request;
    }

    /**
     * Return site instance
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * Return router instance
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Return cache instance
     */
    public function cache(): SiteCache
    {
        return $this->cache;
    }

    /**
     * Return languages instance
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Return translations instance
     */
    public function translations(): Translations
    {
        return $this->translations;
    }

    /**
     * Return default options
     */
    public function defaults(): array
    {
        return PHP::parseFile(FORMWORK_PATH . 'defaults.php');
    }

    /**
     * Run Formwork
     */
    public function run(): void
    {
        $resource = $this->router->dispatch();

        if ($resource instanceof Page) {
            if ($this->site->currentPage() === null) {
                $this->site->setCurrentPage($resource);
            }

            $page = $this->site->currentPage();

            if ($this->config()->get('cache.enabled') && $this->cache->has($this->request)) {
                $response = $this->cache->fetch($this->request);
                $response->render();
            } else {
                $content = $page->render();

                if ($this->config()->get('cache.enabled') && $page->cacheable()) {
                    $this->cache->save($this->request, new Response(
                        $content,
                        $page->get('response_status'),
                        $page->headers()
                    ));
                }
            }
        }

        if ($this->config()->get('statistics.enabled') && isset($page) && !$page->isErrorPage()) {
            $statistics = new Statistics();
            $statistics->trackVisit();
        }
    }

    /**
     * Load options
     */
    protected function loadConfig(): void
    {
        // Default config is needed to parse YAML
        $this->config = new Config($this->defaults());

        $data = YAML::parseFile(CONFIG_PATH . 'system.yml');

        if ($data !== []) {
            if (isset($data['admin.root'])) {
                // Trim slashes from admin.root
                $data['admin.root'] = trim($data['admin.root'], '/');
            }
            $this->config = new Config(array_replace_recursive($this->defaults(), $data));
        }

        date_default_timezone_set($this->config->get('date.timezone'));
    }

    /**
     * Load language from request
     */
    protected function loadLanguages(): void
    {
        $this->languages = Languages::fromRequest($this->request);

        if ($this->languages->requested() !== null) {
            $this->request = Str::removeStart($this->request, '/' . $this->languages->current());
        } elseif ($this->languages->preferred() !== null) {
            // Don't redirect if we are in Admin
            if (!Str::startsWith($this->request, '/' . $this->config()->get('admin.root'))) {
                Header::redirect(HTTPRequest::root() . $this->languages->preferred() . $this->request);
            }
        }
    }

    /**
     * Load translations
     */
    protected function loadTranslations(): void
    {
        $this->translations = Translations::fromPath($this->config()->get('translations.paths.system'));
        $this->translations->setCurrent($this->languages->current() ?? $this->config()->get('translations.fallback'));
    }

    /**
     * Load site
     */
    protected function loadSite(): void
    {
        FileSystem::assertExists(CONFIG_PATH . 'site.yml');
        $config = YAML::parseFile(CONFIG_PATH . 'site.yml');
        $this->site = new Site($config);
        $this->site->setLanguages($this->languages);
    }

    /**
     * Load cache
     */
    protected function loadCache(): void
    {
        $this->cache = new SiteCache($this->config()->get('cache.path'), $this->config()->get('cache.time'));
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        $this->router = new Router($this->request);

        if ($this->config()->get('admin.enabled')) {
            $this->loadAdminRoute();
        }

        $this->router->add([
            '/',
            '/page/{paginationPage:num}/',
            '/{page}/tag/{tagName:aln}/page/{paginationPage:num}/',
            '/{page}/tag/{tagName:aln}/',
            '/{page}/page/{paginationPage:num}/',
            '/{page}/'
        ], $this->defaultRoute());
    }

    /**
     * Load admin route
     */
    protected function loadAdminRoute(): void
    {
        $this->router->add(
            ['HTTP', 'XHR'],
            ['GET', 'POST'],
            [
                '/' . $this->config()->get('admin.root') . '/',
                '/' . $this->config()->get('admin.root') . '/{route}/'
            ],
            Admin::class . '@run'
        );
    }

    /**
     * Get default route
     */
    protected function defaultRoute(): callable
    {
        return function (RouteParams $params) {
            $route = $params->get('page', $this->config()->get('pages.index'));

            if ($this->site->has('aliases') && $alias = $this->site->alias($route)) {
                $route = trim($alias, '/');
            }

            if ($page = $this->site->findPage($route)) {
                if ($page->has('canonical')) {
                    $canonical = trim($page->canonical(), '/');
                    if ($params->get('page', '') !== $canonical) {
                        $route = empty($canonical) ? '' : $this->router->rewrite(['page' => $canonical]);
                        Header::redirect($this->site->uri($route), 301);
                    }
                }
                if (($params->has('tagName') || $params->has('paginationPage')) && $page->template()->scheme()->get('type') !== 'listing') {
                    return $this->site->errorPage();
                }
                if ($this->config()->get('cache.enabled') && ($page->has('publish-date') || $page->has('unpublish-date'))) {
                    if (($page->published() && !$this->site->modifiedSince(Date::toTimestamp($page->get('publish-date'))))
                    || (!$page->published() && !$this->site->modifiedSince(Date::toTimestamp($page->get('unpublish-date'))))) {
                        // Clear cache if the site was not modified since the page has been published or unpublished
                        $this->cache->clear();
                        FileSystem::touch($this->config()->get('content.path'));
                    }
                }
                if ($page->routable() && $page->published()) {
                    return $page;
                }
            } else {
                $filename = basename($route);
                $upperLevel = dirname($route);
                if ($upperLevel === '.') {
                    $upperLevel = $this->config()->get('pages.index');
                }
                if (($parent = $this->site->findPage($upperLevel)) && $parent->files()->has($filename)) {
                    return HTTPResponse::file($parent->files()->get($filename)->path());
                }
            }

            return $this->site->errorPage();
        };
    }
}
