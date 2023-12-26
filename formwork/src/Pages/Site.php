<?php

namespace Formwork\Pages;

use Formwork\App;
use Formwork\Config\Config;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Languages\Languages;
use Formwork\Metadata\MetadataCollection;
use Formwork\Pages\Exceptions\PageNotFoundException;
use Formwork\Pages\Templates\TemplateCollection;
use Formwork\Pages\Templates\TemplateFactory;
use Formwork\Pages\Traits\PageData;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Scheme;
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;

class Site implements Arrayable
{
    use PageData;
    use PageTraversal;
    use PageUid;
    use PageUri;

    /**
     * Site path
     */
    protected ?string $path = null;

    /**
     * Site relative path
     */
    protected ?string $relativePath = null;

    /**
     * Site content file
     */
    protected ?ContentFile $contentFile = null;

    /**
     * Site route
     */
    protected ?string $route = null;

    /**
     * Site canonical route
     */
    protected ?string $canonicalRoute = null;

    /**
     * Site slug
     */
    protected ?string $slug = null;

    /**
     * Site languages
     */
    protected Languages $languages;

    /**
     * Site scheme
     */
    protected Scheme $scheme;

    /**
     * Site fields
     */
    protected FieldCollection $fields;

    /**
     * Site templates
     */
    protected TemplateCollection $templates;

    /**
     * Site metadata
     */
    protected MetadataCollection $metadata;

    /**
     * Site storage (loaded pages)
     *
     * @var array<string, Page>
     */
    protected array $storage = [];

    /**
     * Site current page
     */
    protected ?Page $currentPage = null;

    /**
     * Site aliases
     *
     * @var array<string, string>
     */
    protected array $routeAliases;

    /**
     * Create a new Site instance
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected App $app,
        protected Config $config,
        protected TemplateFactory $templateFactory
    ) {
        $this->setMultiple($data);
    }

    public function __toString()
    {
        return $this->title();
    }

    public function site(): Site
    {
        return $this;
    }

    /**
     * Return site default data
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = Yaml::parseFile(SYSTEM_PATH . '/config/site.yaml');

        return [...$defaults, ...Arr::reject($this->fields()->pluck('default'), fn ($value) => $value === null)];
    }

    public function parent(): Page|Site|null
    {
        return $this->parent ??= null;
    }

    public function siblings(): PageCollection
    {
        return $this->siblings ??= new PageCollection([]);
    }

    public function inclusiveSiblings(): PageCollection
    {
        return $this->inclusiveSiblings ??= new PageCollection([$this->route() => $this]);
    }

    /**
     * Get site path
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Get site relative path
     */
    public function relativePath(): ?string
    {
        return $this->relativePath;
    }

    /**
     * Get site filename
     */
    public function contentFile(): ?ContentFile
    {
        return $this->contentFile;
    }

    /**
     * Get site route
     */
    public function route(): ?string
    {
        return $this->route;
    }

    /**
     * Get site canonical route
     */
    public function canonicalRoute(): ?string
    {
        return $this->canonicalRoute;
    }

    /**
     * Get site slug
     */
    public function slug(): ?string
    {
        return $this->slug;
    }

    /**
     * Get site languages
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Get site scheme
     */
    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    /**
     * Get site fields
     */
    public function fields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Get site templates
     */
    public function templates(): TemplateCollection
    {
        return $this->templates;
    }

    /**
     * Get the current page of the site
     */
    public function currentPage(): ?Page
    {
        return $this->currentPage;
    }

    /**
     * Get site route aliases
     *
     * @return array<string, string>
     */
    public function routeAliases(): array
    {
        return $this->routeAliases;
    }

    /**
     * Get site metadata
     */
    public function metadata(): MetadataCollection
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $defaults = [
            'charset'     => $this->config->get('system.charset'),
            'author'      => $this->get('author'),
            'description' => $this->get('description'),
            'generator'   => 'Formwork',
        ];

        $data = array_filter([...$defaults, ...$this->data['metadata']]);

        if (!$this->config->get('system.metadata.setGenerator')) {
            unset($data['generator']);
        }

        return $this->metadata = new MetadataCollection($data);
    }

    /**
     * Return whether site has been modified since given time
     */
    public function modifiedSince(int $time): bool
    {
        if ($this->path === null) {
            return false;
        }
        return FileSystem::directoryModifiedSince($this->path, $time);
    }

    /**
     * Return a PageCollection containing site pages
     */
    public function pages(): PageCollection
    {
        return $this->children();
    }

    /**
     * Return whether site has pages
     */
    public function hasPages(): bool
    {
        return $this->hasChildren();
    }

    /**
     * Retrieve page from the storage creating a new one if not existing
     */
    public function retrievePage(string $path): Page
    {
        if (isset($this->storage[$path])) {
            return $this->storage[$path];
        }
        return $this->storage[$path] = new Page(['site' => $this, 'path' => $path]);
    }

    public function retrievePages(string $path, bool $recursive = false): PageCollection
    {
        /**
         * @var array<string, Page>
         */
        $pages = [];

        foreach (FileSystem::listDirectories($path) as $dir) {
            $pagePath = FileSystem::joinPaths($path, $dir, DS);

            if ($dir[0] !== '_' && FileSystem::isDirectory($pagePath)) {
                $page = $this->retrievePage($pagePath);

                if ($page->hasContentFile()) {
                    $pages[$page->route()] = $page;
                }

                if ($recursive) {
                    $pages = [...$pages, ...$this->retrievePages($pagePath, recursive: true)->toArray()];
                }
            }
        }

        $pageCollection = new PageCollection($pages);

        return $pageCollection->sortBy('relativePath');
    }

    /**
     * Find page from route
     */
    public function findPage(string $route): ?Page
    {
        if ($route === '/') {
            return $this->indexPage();
        }

        $components = explode('/', trim($route, '/'));
        $path = $this->path;

        if ($path === null) {
            return null;
        }

        foreach ($components as $component) {
            $found = false;
            foreach (FileSystem::listDirectories($path) as $dir) {
                if (preg_replace(Page::NUM_REGEX, '', $dir) === $component) {
                    $path .= $dir . '/';
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }

        $page = $this->retrievePage($path);

        return $page;
    }

    /**
     * Set and return site current page
     */
    public function setCurrentPage(Page $page): Page
    {
        return $this->currentPage = $page;
    }

    /**
     * Return alias of a given route
     */
    public function resolveRouteAlias(string $route): ?string
    {
        return $this->routeAliases[$route] ?? null;
    }

    /**
     * Get site index page
     */
    public function indexPage(): Page
    {
        return $this->findPage($this->config->get('system.pages.index'))
            ?? throw new PageNotFoundException('Site index page not found');
    }

    /**
     * Return or render site error page
     */
    public function errorPage(): Page
    {
        return $this->findPage($this->config->get('system.pages.error'))
            ?? throw new PageNotFoundException('Site error page not found');
    }

    /**
     * Return whether the page is site
     */
    public function isSite(): bool
    {
        return true;
    }

    /**
     * Return whether the page is the index page
     */
    public function isIndexPage(): bool
    {
        return false;
    }

    /**
     * Return whether the page is the error page
     */
    public function isErrorPage(): bool
    {
        return false;
    }

    /**
     * Return whether the page is deletable
     */
    public function isDeletable(): bool
    {
        return false;
    }

    public function schemes(): Schemes
    {
        return $this->app->schemes();
    }

    public function load(): void
    {
        $this->scheme = $this->app->schemes()->get('config.site');

        $this->fields = $this->scheme->fields();

        $this->loadTemplates();

        $this->data = [...$this->defaults(), ...$this->data];

        $this->loadRouteAliases();

        $this->fields->setValues($this->data);
    }

    protected function loadTemplates(): void
    {
        $path = $this->config->get('system.templates.path');

        $templates = [];

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'php') {
                $name = FileSystem::name($file);
                $templates[$name] = $this->templateFactory->make($name);
            }
        }

        $this->templates = new TemplateCollection($templates);
    }

    /**
     * Site storage
     *
     * @return array<string, Page>
     */
    protected function storage(): array
    {
        return $this->storage;
    }

    protected function setPath(string $path): void
    {
        $this->path = FileSystem::normalizePath($path . '/');

        $this->relativePath = DS;

        $this->route = '/';

        $this->slug = '';
    }

    /**
     * Load site aliases
     */
    protected function loadRouteAliases(): void
    {
        $this->routeAliases = [];
        foreach ($this->data['routeAliases'] as $from => $to) {
            $this->routeAliases[trim($from, '/')] = trim($to, '/');
        }
    }
}
