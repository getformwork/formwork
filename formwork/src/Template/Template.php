<?php

namespace Formwork\Template;

use Formwork\Assets;
use Formwork\Formwork;
use Formwork\Page;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\View\Renderer;
use Formwork\View\View;

class Template extends View
{
    /**
     * @inheritdoc
     */
    protected const TYPE = 'template';

    /**
     * Page passed to the template
     */
    protected Page $page;

    /**
     * Template assets instance
     */
    protected Assets $assets;

    /**
     * Create a new Template instance
     */
    public function __construct(string $name, Page $page)
    {
        $this->page = $page;
        parent::__construct($name, [], Formwork::instance()->config()->get('templates.path'));
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if (isset($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets(
            $this->path() . 'assets' . DS,
            Formwork::instance()->site()->uri('/site/templates/assets/', false)
        );
    }

    /**
     * Insert a template
     */
    public function insert(string $name, array $vars = []): void
    {
        if (Str::startsWith($name, '_')) {
            $name = 'partials' . DS . Str::removeStart($name, '_');
        }

        parent::insert($name, $vars);
    }

    /**
     * Render template
     */
    public function render(bool $return = false)
    {
        $isCurrentPage = $this->page->isCurrent();

        $this->loadController();

        // Render correct page if the controller has changed the current one
        if ($isCurrentPage && !$this->page->isCurrent()) {
            return Formwork::instance()->site()->currentPage()->template()->render($return);
        }

        return parent::render($return);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected function createLayoutView(string $name): View
    {
        return new static('layouts' . DS . $name, $this->page);
    }

    /**
     * Load template controller if exists
     */
    protected function loadController(): void
    {
        $controllerFile = $this->path . 'controllers' . DS . $this->name . '.php';

        if (FileSystem::exists($controllerFile)) {
            $this->vars = array_merge($this->vars, (array) Renderer::load($controllerFile, $this->vars, $this));
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
