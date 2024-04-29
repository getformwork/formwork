<?php

namespace Formwork\Pages\Templates;

use Closure;
use Formwork\App;
use Formwork\Assets;
use Formwork\Pages\Page;
use Formwork\Pages\Site;
use Formwork\Utils\FileSystem;
use Formwork\View\Exceptions\RenderingException;
use Formwork\View\Renderer;
use Formwork\View\View;
use Stringable;

class Template extends View implements Stringable
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
    public function __construct(string $name, protected App $app, protected Site $site)
    {
        parent::__construct($name, $this->defaults(), $this->app->config()->get('system.templates.path'), $this->defaultMethods());
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
        return $this->assets ?? ($this->assets = new Assets(
            FileSystem::joinPaths($this->path(), 'assets'),
            $this->site->uri('/site/templates/assets/', includeLanguage: false)
        ));
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
            if ($this->site->currentPage() === null) {
                throw new RenderingException('Invalid current page');
            }
            return $this->site->currentPage()->render();
        }

        return parent::render();
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;
        $this->vars['page'] = $page;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'router' => $this->app->router(),
            'site'   => $this->site,
        ];
    }

    /**
     * Default template methods
     *
     * @return array<string, Closure>
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
        $controllerFile = FileSystem::joinPaths($this->path, 'controllers', $this->name . '.php');

        if (FileSystem::exists($controllerFile)) {
            $this->allowMethods = true;
            $this->vars = [...$this->vars, ...(array) Renderer::load($controllerFile, $this->vars, $this)];
            $this->allowMethods = false;
        }
    }
}
