<?php

namespace Formwork\Pages\Templates;

use Closure;
use Formwork\App;
use Formwork\Assets;
use Formwork\Pages\Page;
use Formwork\Pages\Site;
use Formwork\Utils\Constraint;
use Formwork\Utils\FileSystem;
use Formwork\View\Exceptions\RenderingException;
use Formwork\View\Renderer;
use Formwork\View\ViewFactory;
use Stringable;

class Template implements Stringable
{
    /**
     * Template assets instance
     */
    protected Assets $assets;

    protected string $path;

    /**
     * Create a new Template instance
     */
    public function __construct(protected string $name, protected App $app, protected Site $site, protected ViewFactory $viewFactory)
    {
        $this->path = $this->app->config()->get('system.templates.path');
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        return $this->assets ?? ($this->assets = new Assets(
            FileSystem::joinPaths($this->path, 'assets'),
            $this->site->uri('/site/templates/assets/', includeLanguage: false)
        ));
    }

    /**
     * Render template
     *
     * @param array<string, mixed> $vars
     */
    public function render(array $vars = []): string
    {
        if (!Constraint::hasKeys($vars, ['page'])) {
            throw new RenderingException('Missing "page" variable');
        }

        $page = $vars['page'];

        $isCurrentPage = $page->isCurrent();

        $controllerVars = $this->loadController($vars) ?? [];

        // Render correct page if the controller has changed the current one
        if ($isCurrentPage && !$page->isCurrent()) {
            if ($this->site->currentPage() === null) {
                throw new RenderingException('Invalid current page');
            }
            return $this->site->currentPage()->render();
        }

        $view = $this->viewFactory->make(
            $this->name,
            [...$this->defaultVars(), ...$vars, ...$controllerVars],
            $this->path,
            [...$this->defaultMethods()]
        );

        return $view->render();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultVars(): array
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
     *
     * @param array<string, mixed> $vars
     *
     * @return array<string, mixed>|null
     */
    protected function loadController(array $vars = []): ?array
    {
        $controllerFile = FileSystem::joinPaths($this->path, 'controllers', $this->name . '.php');

        if (FileSystem::exists($controllerFile)) {
            return (array) Renderer::load($controllerFile, [...$this->defaultVars(), ...$vars], $this);
        }

        return null;
    }
}
