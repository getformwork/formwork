<?php

namespace Formwork\Core;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Cache\SiteCache;
use Formwork\Languages\Languages;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Traits\SingletonTrait;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

class Formwork
{
    use SingletonTrait;

    /**
     * Current Formwork version
     *
     * @var string
     */
    public const VERSION = '1.10.2';

    /**
     * Array containing options
     *
     * @var array
     */
    protected $options = [];

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

        $this->loadOptions();
        $this->loadLanguages();
        $this->loadSite();
        $this->loadCache();
        $this->loadRoutes();
    }

    /**
     * Get system options
     */
    public function options(): array
    {
        return $this->options;
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
     * Return default options
     */
    public function defaults(): array
    {
        return [
            'charset'                  => 'utf-8',
            'date.format'              => 'm/d/Y',
            'date.hour_format'         => 'h:i A',
            'date.timezone'            => 'UTC',
            'date.week_starts'         => 0,
            'languages.available'      => [],
            'languages.http_preferred' => false,
            'content.path'             => ROOT_PATH . 'content' . DS,
            'content.extension'        => '.md',
            'files.allowed_extensions' => ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.pdf'],
            'parsers.use_php_yaml'     => 'parse',
            'templates.path'           => ROOT_PATH . 'templates' . DS,
            'templates.extension'      => '.php',
            'pages.index'              => 'index',
            'pages.error'              => '404',
            'cache.enabled'            => false,
            'cache.path'               => ROOT_PATH . 'cache' . DS,
            'cache.time'               => 604800,
            'images.jpeg_quality'      => 85,
            'images.jpeg_progressive'  => true,
            'images.png_compression'   => 6,
            'images.webp_quality'      => 85,
            'images.process_uploads'   => true,
            'backup.path'              => ROOT_PATH . 'backup' . DS,
            'backup.max_files'         => 10,
            'updates.backup_before'    => true,
            'metadata.set_generator'   => true,
            'statistics.enabled'       => true,
            'admin.enabled'            => true,
            'admin.root'               => 'admin',
            'admin.lang'               => 'en',
            'admin.login_attempts'     => 10,
            'admin.login_reset_time'   => 300,
            'admin.logout_redirect'    => 'login',
            'admin.session_timeout'    => 20,
            'admin.avatar_size'        => 512
        ];
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

            if ($this->option('cache.enabled') && $this->cache->has($this->request)) {
                $response = $this->cache->fetch($this->request);
                $response->render();
            } else {
                $content = $page->render();

                if ($this->option('cache.enabled') && $page->cacheable()) {
                    $this->cache->save($this->request, new Response(
                        $content,
                        $page->get('response_status'),
                        $page->headers()
                    ));
                }
            }
        }

        if ($this->option('statistics.enabled')) {
            if (isset($page) && !$page->isErrorPage()) {
                $statistics = new Statistics();
                $statistics->trackVisit();
            }
        }
    }

    /**
     * Get an option value
     *
     * @param mixed|null $default Default value if option is not set
     */
    public function option(string $option, $default = null)
    {
        return array_key_exists($option, $this->options) ? $this->options[$option] : $default;
    }

    /**
     * Load options
     */
    protected function loadOptions(): void
    {
        FileSystem::assert(CONFIG_PATH . 'system.yml');

        // Load defaults before parsing YAML
        $this->options = $this->defaults();

        $config = YAML::parseFile(CONFIG_PATH . 'system.yml');
        $this->options = array_merge($this->options, $config);

        // Trim slashes from admin.root
        $this->options['admin.root'] = trim($this->option('admin.root'), '/');

        date_default_timezone_set($this->option('date.timezone'));
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
            if (!Str::startsWith($this->request, '/' . $this->option('admin.root'))) {
                Header::redirect(HTTPRequest::root() . $this->languages->preferred() . $this->request);
            }
        }
    }

    /**
     * Load site
     */
    protected function loadSite(): void
    {
        FileSystem::assert(CONFIG_PATH . 'site.yml');
        $config = YAML::parseFile(CONFIG_PATH . 'site.yml');
        $this->site = new Site($config);
        $this->site->setLanguages($this->languages);
    }

    /**
     * Load cache
     */
    protected function loadCache(): void
    {
        $this->cache = new SiteCache($this->option('cache.path'), $this->option('cache.time'));
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        $this->router = new Router($this->request);

        if ($this->option('admin.enabled')) {
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
                '/' . $this->option('admin.root') . '/',
                '/' . $this->option('admin.root') . '/{route}/'
            ],
            Admin::class . '@run'
        );
    }

    /**
     * Get default route
     */
    private function defaultRoute(): callable
    {
        return function (RouteParams $params) {
            $route = $params->get('page', $this->option('pages.index'));

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
                if ($params->has('tagName') || $params->has('paginationPage')) {
                    if ($page->template()->scheme()->get('type') !== 'listing') {
                        return $this->site->errorPage();
                    }
                }
                if ($this->option('cache.enabled') && ($page->has('publish-date') || $page->has('unpublish-date'))) {
                    if (($page->published() && !$this->site->modifiedSince((int) strtotime($page->get('publish-date'))))
                    || (!$page->published() && !$this->site->modifiedSince((int) strtotime($page->get('unpublish-date'))))) {
                        // Clear cache if the site was not modified since the page has been published or unpublished
                        $this->cache->clear();
                        FileSystem::touch($this->option('content.path'));
                    }
                }
                if ($page->routable() && $page->published()) {
                    return $page;
                }
            } else {
                $filename = basename($route);
                $upperLevel = dirname($route);
                if ($upperLevel === '.') {
                    $upperLevel = $this->option('pages.index');
                }
                if ($parent = $this->site->findPage($upperLevel)) {
                    if ($parent->files()->has($filename)) {
                        return HTTPResponse::file($parent->files()->get($filename)->path());
                    }
                }
            }

            return $this->site->errorPage();
        };
    }
}
