<?php

namespace Formwork\Pages;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Formwork;
use Formwork\Languages\Languages;
use Formwork\Metadata\MetadataCollection;
use Formwork\Pages\Templates\TemplateCollection;
use Formwork\Pages\Traits\PageData;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Schemes\Scheme;
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
     */
    protected array $storage = [];

    /**
     * Site current page
     */
    protected ?Page $currentPage = null;

    /**
     * Site aliases
     */
    protected array $routeAliases;

    /**
     * Create a new Site instance
     */
    public function __construct(array $data = [])
    {
        $this->setMultiple($data);

        $this->load();

        $this->fields->validate($this->data);
    }

    public function __toString()
    {
        return $this->title();
    }

    public static function fromPath(string $path, array $data = []): static
    {
        return new static(['path' => $path] + $data);
    }

    /**
     * Return site default data
     */
    public function defaults(): array
    {
        $defaults = [
            'title'          => 'Formwork',
            'author'         => '',
            'description'    => '',
            'metadata'       => [],
            'canonicalRoute' => null,
            'routeAliases'   => []
        ];

        $defaults = array_merge($defaults, Arr::reject($this->fields()->pluck('default'), fn ($value) => $value === null));

        return $defaults;
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
    public function relativePath(): string
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
    public function route(): string
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
    public function slug(): string
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
            'charset'      => Formwork::instance()->config()->get('charset'),
            'author'       => $this->get('author'),
            'description'  => $this->get('description'),
            'generator'    => 'Formwork',
            'routeAliases' => []
        ];

        $data = array_filter(array_merge($defaults, $this->data['metadata']));

        if (!Formwork::instance()->config()->get('metadata.setGenerator')) {
            unset($data['generator']);
        }

        return $this->metadata = new MetadataCollection($data);
    }

    /**
     * Return whether site has been modified since given time
     */
    public function modifiedSince(int $time): bool
    {
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
        return $this->storage[$path] = Page::fromPath($path);
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

        foreach ($components as $component) {
            $found = false;
            foreach (FileSystem::listDirectories($path) as $dir) {
                if (preg_replace(Page::NUM_REGEX, '', $dir) === $component) {
                    $path .= $dir . DS;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }

        $page = $this->retrievePage($path);

        return $page->hasContentFile() ? $page : null;
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
    public function indexPage(): ?Page
    {
        return $this->findPage(Formwork::instance()->config()->get('pages.index'));
    }

    /**
     * Return or render site error page
     */
    public function errorPage(): ?Page
    {
        return $this->findPage(Formwork::instance()->config()->get('pages.error'));
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

    protected function load()
    {
        $this->scheme = Formwork::instance()->schemes()->get('config', 'site');

        $this->fields = $this->scheme->fields();

        $this->templates = TemplateCollection::fromPath(Formwork::instance()->config()->get('templates.path'));

        $this->data = array_merge($this->defaults(), $this->data);

        $this->loadRouteAliases();
    }

    /**
     * Site storage
     */
    protected function storage(): array
    {
        return $this->storage;
    }

    protected function setPath(string $path): void
    {
        $this->path = FileSystem::normalizePath($path . DS);

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
