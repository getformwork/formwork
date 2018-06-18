<?php

namespace Formwork\Core;
use Formwork\Cache\Cache;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use Exception;
use Spyc;

class Formwork {

    const VERSION = '0.6.9';

    protected static $instance;

    protected $options = array();

    protected $site;

    protected $router;

    protected $cache;

    protected $cacheKey;

    protected $resource;

    public static function instance() {
        if (!is_null(static::$instance)) return static::$instance;
        return static::$instance = new static;
    }

    public function defaults() {
        return array(
            'date.format'              => 'm/d/Y',
            'date.hour_format'         => 'h:i A',
            'date.timezone'            => 'UTC',
            'date.week_starts'         => 0,
            'content.path'             => ROOT_PATH . 'content' . DS,
            'content.extension'        => '.md',
            'files.allowed_extensions' => array('.jpg', '.jpeg', '.png', '.gif', '.svg', '.pdf'),
            'templates.path'           => ROOT_PATH . 'templates' . DS,
            'templates.extension'      => '.php',
            'pages.index'              => 'index',
            'pages.error'              => '404',
            'cache.enabled'            => false,
            'cache.path'               => ROOT_PATH . 'cache' . DS,
            'cache.time'               => 604800,
            'admin.enabled'            => true,
            'admin.lang'               => 'en'
        );
    }

    public function __construct() {
        if (!is_null(static::$instance)) throw new Exception('Formwork class already instantiated');
        static::$instance = $this;

        FileSystem::assert(CONFIG_PATH . 'system.yml');
        FileSystem::assert(CONFIG_PATH . 'site.yml');

        $config = Spyc::YAMLLoad(CONFIG_PATH . 'system.yml');
        $this->options = array_merge($this->defaults(), $config);

        date_default_timezone_set($this->option('date.timezone'));

        $siteConfig = Spyc::YAMLLoad(CONFIG_PATH . 'site.yml');
        $this->site = new Site($siteConfig);

        $this->router = new Router(Uri::removeQuery(HTTPRequest::uri()));

        if ($this->option('cache.enabled')) {
            $this->cache = new Cache();
            $this->cacheKey = sha1(trim(HTTPRequest::uri(), '/'));
        }
    }

    public function run() {

        if ($this->option('cache.enabled') && $data = $this->cache->fetch($this->cacheKey)) {
            echo $data;
            return;
        }

        $this->router->add(array(
            '/',
            '/page/{paginationPage:num}/',
            '/{page}/page/{paginationPage:num}/',
            '/{page}/'
        ), array($this, 'defaultRoute'));

        $this->resource = $this->router->dispatch();

        if ($this->resource instanceof Page) {
            $data = $this->resource->render();
            if ($this->option('cache.enabled') && $this->resource->get('cacheable')) {
                $this->cache->save($this->cacheKey, $data);
            }
        }

    }

    public function defaultRoute(RouteParams $params) {
        $path = $params->get('page', $this->option('pages.index'));

        if ($this->site->has('aliases') && $alias = $this->site->alias($path)) {
            $path = trim($alias, '/');
        }

        if ($page = $this->site->findPage($path)) {
            if ($params->get('paginationPage') == 1) Header::redirect($page->uri(), 301, true);
            if ($page->routable() && $page->published()) return $page;
        } else {
            $filename = FileSystem::basename($path);
            $upperLevel = FileSystem::dirname($path);
            if ($parent = $this->site->findPage($upperLevel)) {
                if ($file = $parent->file($filename)) {
                    return Header::file($file);
                }
            }
        }

        return $this->site->errorPage(true);
    }

    public function option($option) {
        return array_key_exists($option, $this->options) ? $this->options[$option] : null;
    }

    public function __call($name, $arguments) {
        if (property_exists($this, $name)) return $this->$name;
        throw new Exception('Invalid method');
    }

}
