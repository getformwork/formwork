<?php

namespace Formwork\Pages\Templates;

use Formwork\Assets;
use Formwork\Formwork;
use Formwork\Pages\Page;
use Formwork\Utils\FileSystem;
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
        parent::__construct($name, [], Formwork::instance()->config()->get('templates.path'), $this->defaultMethods());
    }

    public function __toString(): string
    {
        return $this->name;
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
     * Render template
     */
    public function render(): string
    {
        $isCurrentPage = $this->page->isCurrent();

        $this->loadController();

        // Render correct page if the controller has changed the current one
        if ($isCurrentPage && !$this->page->isCurrent()) {
            return Formwork::instance()->site()->currentPage()->render();
        }

        return parent::render();
    }

    /**
     * @inheritdoc
     */
    protected function defaults(): array
    {
        return [
            'params' => Formwork::instance()->router()->params(),
            'site'   => Formwork::instance()->site(),
            'page'   => $this->page,
        ];
    }

    /**
     * Default template methods
     */
    protected function defaultMethods(): array
    {
        return [
            'assets' => fn () => $this->assets(),
        ];
    }

    /**
     * Load template controller if exists
     */
    protected function loadController(): void
    {
        $controllerFile = $this->path . 'controllers' . DS . $this->name . '.php';

        if (FileSystem::exists($controllerFile)) {
            $this->allowMethods = true;
            $this->vars = array_merge($this->vars, (array) Renderer::load($controllerFile, $this->vars, $this));
            $this->allowMethods = false;
        }
    }
}
