<?php

namespace Formwork\Core;

use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;

class Site extends AbstractPage
{
    /**
     * Array containing all loaded pages
     *
     * @var array
     */
    public static $storage = array();

    /**
     * Current page
     *
     * @var Page
     */
    protected $currentPage;

    /**
     * Array containing all available templates
     *
     * @var array
     */
    protected $templates = array();

    /**
     * Create a new Site instance
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->path = Formwork::instance()->option('content.path');
        $this->route = '/';
        $this->uri = HTTPRequest::root();
        $this->data = array_merge($this->defaults(), $data);
        $this->loadTemplates();
    }

    /**
     * Return site default data
     *
     * @return array
     */
    public function defaults()
    {
        return array(
            'lang'  => 'en',
            'title' => 'Formwork'
        );
    }

    /**
     * Get all available templates
     *
     * @return array
     */
    public function templates()
    {
        return array_map('strval', array_keys($this->templates));
    }

    /**
     * Return whether a template exists
     *
     * @param string $template
     *
     * @return bool
     */
    public function hasTemplate($template)
    {
        return array_key_exists($template, $this->templates);
    }

    /**
     * Return whether site has been modified since given time
     *
     * @param int $time
     *
     * @return bool
     */
    public function modifiedSince($time)
    {
        return FileSystem::directoryModifiedSince($this->path, $time);
    }

    /**
     * @inheritdoc
     */
    public function parent()
    {
        return null;
    }

    /**
     * Return a PageCollection containing site pages
     *
     * @return PageCollection
     */
    public function pages()
    {
        return $this->children();
    }

    /**
     * Return whether site has pages
     *
     * @return bool
     */
    public function hasPages()
    {
        return !$this->children()->isEmpty();
    }

    /**
     * Return alias of a given route
     *
     * @param string $route
     *
     * @return string|null
     */
    public function alias($route)
    {
        if ($this->has('aliases')) {
            $route = trim($route, '/');
            if (isset($this->data['aliases'][$route])) {
                return $this->data['aliases'][$route];
            }
        }
    }

    /**
     * Get site current page
     *
     * @return Page|null
     */
    public function currentPage()
    {
        if (!empty($this->currentPage)) {
            return $this->currentPage;
        }
        $resource = Formwork::instance()->resource();
        if ($resource instanceof Page) {
            return $this->currentPage = $resource;
        }
        return null;
    }

    /**
     * Get site index page
     *
     * @return Page|null
     */
    public function indexPage()
    {
        return $this->findPage(Formwork::instance()->option('pages.index'));
    }

    /**
     * Return or render site error page
     *
     * @param bool $render Whether to render error page or not
     *
     * @return Page|null
     */
    public function errorPage($render = false)
    {
        $errorPage = $this->findPage(Formwork::instance()->option('pages.error'));
        if ($render) {
            Header::status(404);
            $errorPage->render();
            exit;
        }
        return $errorPage;
    }

    /**
     * @inheritdoc
     */
    public function isSite()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isIndexPage()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isErrorPage()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isDeletable()
    {
        return false;
    }

    /**
     * Find page from route
     *
     * @param string $route
     *
     * @return Page|null
     */
    public function findPage($route)
    {
        if ($route === '/') {
            return $this->indexPage();
        }

        $components = explode('/', trim($route, '/'));
        $path = $this->path;

        foreach ($components as $component) {
            $found = false;
            foreach (FileSystem::listDirectories($path) as $dir) {
                if (preg_replace(Page::NUM_REGEX, '', $dir) === $component) {
                    $path = $path . $dir . DS;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }

        if (isset(static::$storage[$path])) {
            $page = static::$storage[$path];
        } else {
            $page = new Page($path);
            static::$storage[$path] = $page;
        }

        return !$page->isEmpty() ? $page : null;
    }

    /**
     * Load all available templates
     */
    protected function loadTemplates()
    {
        $templatesPath = Formwork::instance()->option('templates.path');
        $templates = array();
        foreach (FileSystem::listFiles($templatesPath) as $file) {
            $templates[FileSystem::name($file)] = $templatesPath . $file;
        }
        $this->templates = $templates;
    }

    public function __toString()
    {
        return 'site';
    }
}
