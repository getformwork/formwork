<?php

namespace Formwork\Core;

use Formwork\Cache\SiteCache;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPNegotiation;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\HTTPResponse;
use Formwork\Utils\Uri;
use LogicException;

class Formwork
{
    /**
     * Current Formwork version
     *
     * @var string
     */
    public const VERSION = '0.12.0';

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
    protected $options = array();

    /**
     * Current request URI
     *
     * @var string
     */
    protected $request;

    /**
     * Array containing loaded languages
     *
     * @var array
     */
    protected $languages = array();

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
     * Current page cache key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Create a new Formwork instance
     */
    public function __construct()
    {
        if (!is_null(static::$instance)) {
            throw new LogicException('Formwork class already instantiated');
        }
        static::$instance = $this;

        Errors::setHandlers();

        $this->request = Uri::removeQuery(HTTPRequest::uri());

        $this->loadOptions();
        $this->loadLanguages();
        $this->loadSite();
        $this->loadCache();

        $this->router = new Router($this->request);
    }

    /**
     * Return self instance
     *
     * @return self
     */
    public static function instance()
    {
        if (!is_null(static::$instance)) {
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
     * Return default options
     *
     * @return array
     */
    public function defaults()
    {
        return array(
            'charset'                  => 'utf-8',
            'date.format'              => 'm/d/Y',
            'date.hour_format'         => 'h:i A',
            'date.timezone'            => 'UTC',
            'date.week_starts'         => 0,
            'languages.available'      => array(),
            'languages.http_preferred' => false,
            'content.path'             => ROOT_PATH . 'content' . DS,
            'content.extension'        => '.md',
            'files.allowed_extensions' => array('.jpg', '.jpeg', '.png', '.gif', '.svg', '.pdf'),
            'parsers.use_php_yaml'     => 'parse',
            'templates.path'           => ROOT_PATH . 'templates' . DS,
            'templates.extension'      => '.php',
            'pages.index'              => 'index',
            'pages.error'              => '404',
            'cache.enabled'            => false,
            'cache.path'               => ROOT_PATH . 'cache' . DS,
            'cache.time'               => 604800,
            'images.jpeg_quality'      => 85,
            'images.png_compression'   => 6,
            'backup.path'              => ROOT_PATH . 'backup' . DS,
            'backup.max_files'         => 10,
            'metadata.set_generator'   => true,
            'admin.enabled'            => true,
            'admin.lang'               => 'en',
            'admin.login_attempts'     => 10,
            'admin.login_reset_time'   => 300,
            'admin.logout_redirect'    => 'login',
            'admin.session_timeout'    => 20,
            'admin.avatar_size'        => 512
        );
    }

    /**
     * Run Formwork
     */
    public function run()
    {
        if ($this->option('cache.enabled') && $output = $this->cache->fetch($this->cacheKey)) {
            if ($output instanceof Output) {
                $output->sendHeaders();
                echo $output->content();
            } else {
                echo $output;
            }
            return;
        }

        $this->router->add(array(
            '/',
            '/page/{paginationPage:num}/',
            '/{page}/tag/{tagName:aln}/page/{paginationPage:num}/',
            '/{page}/tag/{tagName:aln}/',
            '/{page}/page/{paginationPage:num}/',
            '/{page}/'
        ), $this->defaultRoute());

        $resource = $this->router->dispatch();

        if ($resource instanceof Page) {
            if (is_null($this->site->currentPage())) {
                $this->site->setCurrentPage($resource);
            }

            $page = $this->site->currentPage();

            $content = $page->render();

            if ($this->option('cache.enabled') && $page->cacheable()) {
                $output = new Output($content, $page->get('response_status'), $page->headers());
                $this->cache->save($this->cacheKey, $output);
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
        date_default_timezone_set($this->option('date.timezone'));
    }

    /**
     * Load language from request
     */
    protected function loadLanguages()
    {
        $this->languages['available'] = $this->option('languages.available');

        if (empty($this->languages['available'])) {
            return;
        }

        $this->languages['current'] = $this->languages['default'] = $this->option(
            'languages.default',
            $this->languages['available'][0]
        );

        if (preg_match('~^/(' . implode('|', $this->languages['available']) . ')/~i', $this->request, $matches)) {
            list($match, $language) = $matches;
            $this->languages['current'] = $language;
            $this->request = '/' . substr($this->request, strlen($match));
        }

        if ($this->option('languages.http_preferred')) {
            foreach (HTTPNegotiation::language() as $code => $value) {
                if (in_array($code, $this->languages['available'], true)) {
                    // Check if language is already set from request URI
                    if (isset($language)) {
                        $this->languages['preferred'] = $code;
                        break;
                    }
                    if (!defined('ADMIN_PATH')) {
                        // Don't redirect if we are in Admin
                        Header::redirect(HTTPRequest::root() . $code . $this->request);
                    }
                }
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
            $this->cacheKey = Uri::normalize(HTTPRequest::uri());
        }
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
                        $route = empty($canonical) ? '' : $this->router->rewrite(array('page' => $canonical));
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
                    if ($file = $parent->file($filename)) {
                        return HTTPResponse::file($file);
                    }
                }
            }

            return $this->site->errorPage();
        };
    }
}
