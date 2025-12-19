<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Fields\FieldCollection;
use Formwork\Files\Services\FileUploader;
use Formwork\Forms\Form;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Panel;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Stringable;

abstract class AbstractController extends BaseAbstractController
{
    public function __construct(
        private Container $container,
        protected readonly Router $router,
        protected readonly CsrfToken $csrfToken,
        protected readonly Translations $translations,
        protected readonly FileUploader $fileUploader,
        protected readonly Site $site,
        protected readonly Panel $panel,
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * Generate a route by name
     *
     * @param array<string, mixed> $params
     */
    protected function generateRoute(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Get translated string by key
     */
    protected function translate(string $key, int|float|string|Stringable ...$arguments): string
    {
        return $this->translations->getCurrent()->translate($key, ...$arguments);
    }

    /**
     * Get if current user has a permission
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->panel->user()->permissions()->has($permission);
    }

    /**
     * Create a new form instance
     */
    protected function form(string $name, FieldCollection $fieldCollection): Form
    {
        return new Form($name, $fieldCollection, $this->fileUploader);
    }

    /**
     * Load a modal to be rendered later
     */
    protected function modal(string $name): Modal
    {
        $this->panel->modals()->add($name);
        return $this->panel->modals()->get($name);
    }

    /**
     * Render a view
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): string
    {
        $view = $this->viewFactory->make(
            $name,
            [...$this->defaults(), ...$data],
        );
        return $view->render();
    }

    /**
     * Return default data passed to views
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'location'  => $this->name,
            'site'      => $this->site,
            'panel'     => $this->panel,
            'csrfToken' => $this->csrfToken->get($this->panel->getCsrfTokenName()),
        ];
    }
}
