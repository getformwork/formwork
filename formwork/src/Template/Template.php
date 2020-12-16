<?php

namespace Formwork\Template;

use Formwork\Core\Assets;
use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Utils\FileSystem;
use BadMethodCallException;
use RuntimeException;

class Template
{
    /**
     * Template file path
     *
     * @var string
     */
    protected $path;

    /**
     * Template file extension
     *
     * @var string
     */
    protected $extension;

    /**
     * Template name
     *
     * @var string
     */
    protected $name;

    /**
     * Page passed to the template
     *
     * @var Page
     */
    protected $page;

    /**
     * Template variables
     *
     * @var array
     */
    protected $vars = [];

    /**
     * Template scheme
     *
     * @var Scheme
     */
    protected $scheme;

    /**
     * Template assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * Template layout
     *
     * @var Layout
     */
    protected $layout;

    /**
     * Whether template is being rendered
     *
     * @var bool
     */
    protected $rendering = false;

    /**
     * Create a new Template instance
     */
    public function __construct(string $template, Page $page)
    {
        $this->extension = Formwork::instance()->config()->get('templates.extension');
        $this->name = $template;
        $this->page = $page;
        $this->vars = $this->defaults();
    }

    /**
     * Get template path
     */
    public function path(): string
    {
        if ($this->path !== null) {
            return $this->path;
        }
        return $this->path = dirname(Formwork::instance()->site()->template($this->name)) . DS;
    }

    /**
     * Get template name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get template Scheme
     */
    public function scheme(): Scheme
    {
        if ($this->scheme !== null) {
            return $this->scheme;
        }
        return $this->scheme = new Scheme($this->name);
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if ($this->assets !== null) {
            return $this->assets;
        }
        return $this->assets = new Assets(
            $this->path() . 'assets' . DS,
            Formwork::instance()->site()->uri('/site/templates/assets/', false)
        );
    }

    /**
     * Set template layout
     */
    public function layout(string $name): void
    {
        if ($this->layout !== null) {
            throw new RuntimeException('The layout for ' . $this->name . ' template is already set');
        }
        $this->layout = new Layout($name, $this->page, $this);
    }

    /**
     * Insert a template
     */
    public function insert(string $name, array $vars = []): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
        }

        if ($name[0] === '_') {
            $name = 'partials' . DS . substr($name, 1);
        }

        $filename = $this->path() . $name . $this->extension;

        if (!FileSystem::exists($filename)) {
            throw new RuntimeException('Template ' . $name . ' not found');
        }

        Renderer::load($filename, array_merge($this->vars, $vars), $this);
    }

    /**
     * Render template
     *
     * @param bool $return Whether to return rendered content or not
     *
     * @return string|void
     */
    public function render(array $vars = [], bool $return = false)
    {
        if ($this->rendering) {
            throw new RuntimeException(__METHOD__ . '() not allowed while rendering');
        }

        $this->layout = null;

        $this->vars = array_merge($this->vars, $vars);

        $isCurrentPage = $this->page->isCurrent();

        $this->loadController();

        // Render correct page if the controller has changed the current one
        if ($isCurrentPage && !$this->page->isCurrent()) {
            return Formwork::instance()->site()->currentPage()->template()->render($vars, $return);
        }

        ob_start();

        $this->rendering = true;

        $this->insert($this->name);

        if ($this->layout !== null) {
            $this->layout->vars = $this->vars;

            $this->layout->content = ob_get_contents();
            ob_clean(); // Clean but don't end output buffer

            $this->layout->render();
        }

        $this->rendering = false;

        if ($return) {
            return ob_get_clean();
        }

        ob_end_flush();
    }

    /**
     * Return an array containing the default data
     */
    protected function defaults(): array
    {
        return [
            'params' => Formwork::instance()->router()->params(),
            'site'   => Formwork::instance()->site(),
            'page'   => $this->page
        ];
    }

    /**
     * Load template controller if exists
     */
    protected function loadController(): void
    {
        $controllerFile = $this->path() . 'controllers' . DS . $this->name . '.php';

        if (FileSystem::exists($controllerFile)) {
            $this->vars = array_merge($this->vars, (array) Renderer::load($controllerFile, $this->vars, $this));
        }
    }

    public function __call(string $name, array $arguments)
    {
        if (TemplateHelpers::has($name)) {
            if (!$this->rendering) {
                throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
            }

            $helper = TemplateHelpers::get($name);
            return $helper(...$arguments);
        }
        throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
