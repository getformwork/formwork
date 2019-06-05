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
    const VERSION = '0.12.1';

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
     * Default language code
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Preferred language code
     *
     * @var string
     */
    protected $preferredLanguage;

    /**
     * Current language code
     *
     * @var string
     */
    protected $language;

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

        FileSystem::assert(CONFIG_PATH . 'system.yml');
        FileSystem::assert(CONFIG_PATH . 'site.yml');

        $this->options = $this->defaults();
        $config = YAML::parseFile(CONFIG_PATH . 'system.yml');
        $this->options = array_merge($this->options, $config);

        date_default_timezone_set($this->option('date.timezone'));

        $this->request = Uri::removeQuery(HTTPRequest::uri());

        $this->loadLanguages();

        $this->router = new Router($this->request);

        $siteConfig = YAML::parseFile(CONFIG_PATH . 'site.yml');
        $this->site = new Site($siteConfig);

        if ($this->option('cache.enabled')) {
            $this->cache = new SiteCache($this->option('cache.path'), $this->option('cache.time'));
            $this->cacheKey = Uri::normalize(HTTPRequest::uri());
        }
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
     * Load language from request
     */
    protected function loadLanguages()
    {
        if (!empty($languages = $this->option('languages.available'))) {
            $this->defaultLanguage = $this->option('languages.default', $languages[0]);

            if (preg_match('~^/(' . implode('|', $languages) . ')/~i', $this->request, $matches)) {
                list($match, $language) = $matches;
                $this->language = $language;
                $this->request = '/' . substr($this->request, strlen($match));
            } else {
                $this->language = $this->defaultLanguage;
            }

            if ($this->option('languages.http_preferred')) {
                $preferredLanguages = array_keys(HTTPNegotiation::language());
                foreach ($preferredLanguages as $code) {
                    if (in_array($code, $languages, true)) {
                        // Check if language is already set from request URI
                        if (isset($language)) {
                            $this->preferredLanguage = $code;
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

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
