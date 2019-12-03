<?php

namespace Formwork\Core;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Cache\SiteCache;
use Formwork\Languages\Languages;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use LogicException;

class Formwork
{
    /**
     * Current Formwork version
     *
     * @var string
     */
    public const VERSION = '1.3.1';

    /**
     * Formwork instance
     *
     * @var Formwork
     */
    protected static $instance;

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
        if (static::$instance !== null) {
            throw new LogicException('Formwork class already instantiated');
        }
        static::$instance = $this;

        Errors::setHandlers();

        $this->request = Uri::removeQuery(HTTPRequest::uri());

        $this->loadOptions();
        $this->loadLanguages();
        $this->loadSite();
        $this->loadCache();
        $this->loadRoutes();
    }

    /**
     * Return self instance
     *
     * @return self
     */
    public static function instance()
    {
        if (static::$instance !== null) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    /**
     * Get system options
     *
     * @return array
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Get current request
     *
     * @return string
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Return site instance
     *
     * @return Site
     */
    public function site()
    {
        return $this->site;
    }

    /**
     * Return router instance
     *
     * @return Router
     */
    public function router()
    {
        return $this->router;
    }

    /**
     * Return cache instance
     *
     * @return SiteCache
     */
    public function cache()
    {
        return $this->cache;
    }

    /**
     * Return default options
     *
     * @return array
     */
    public function defaults()
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
            'files.allowed_extensions' => ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.pdf'],
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
    public function run()
    {
        $resource = $this->router->dispatch();

        if ($resource instanceof Page) {
            if ($this->site->currentPage() === null) {
                $this->site->setCurrentPage($resource);
            }

            $page = $this->site->currentPage();

            if ($this->option('cache.enabled') && $this->cache->has($page->route())) {
                $response = $this->cache->fetch($page->route());
                $response->render();
            } else {
                $content = $page->render();

                if ($this->option('cache.enabled') && $page->cacheable()) {
                    $this->cache->save($page->route(), new Response(
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
     * @param string     $option
     * @param mixed|null $default Default value if option is not set
     */
    public function option($option, $default = null)
    {
        return array_key_exists($option, $this->options) ? $this->options[$option] : $default;
    }

    /**
     * Load options
     */
    protected function loadOptions()
    {
        FileSystem::assert(CONFIG_PATH . 'system.yml');
        $config = YAML::parseFile(CONFIG_PATH . 'system.yml');
        $this->options = array_merge($this->defaults(), $config);

        // Trim slashes from admin.root
        $this->options['admin.root'] = trim($this->option('admin.root'), '/');

        date_default_timezone_set($this->option('date.timezone'));
    }

    /**
     * Load language from request
     */
    protected function loadLanguages()
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
    protected function loadSite()
    {
        FileSystem::assert(CONFIG_PATH . 'site.yml');
        $config = YAML::parseFile(CONFIG_PATH . 'site.yml');
        $this->site = new Site($config);
        $this->site->set('languages', $this->languages);
    }

    /**
     * Load cache
     */
    protected function loadCache()
    {
        if ($this->option('cache.enabled')) {
            $this->cache = new SiteCache($this->option('cache.path'), $this->option('cache.time'));
        }
    }

    /**
     * Load routes
     */
    protected function loadRoutes()
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
    protected function loadAdminRoute()
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
     *
     * @return callable
     */
    private function defaultRoute()
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
