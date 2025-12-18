<?php

namespace Formwork\Cms;

use Formwork\Config\Config;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Languages\Languages;
use Formwork\Languages\LanguagesFactory;
use Formwork\Metadata\MetadataCollection;
use Formwork\Model\Model;
use Formwork\Pages\ContentFile;
use Formwork\Pages\Exceptions\PageNotFoundException;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Schemes\Schemes;
use Formwork\Templates\Templates;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Stringable;

class Site extends Model implements Stringable
{
    use PageTraversal;
    use PageUid;
    use PageUri;

    protected const string MODEL_IDENTIFIER = 'site';

    /**
     * Site path
     */
    protected ?string $path = null;

    /**
     * Site content path
     */
    protected ?string $contentPath = null;

    /**
     * Site content file
     */
    protected ?ContentFile $contentFile = null;

    /**
     * Site last modified time
     */
    protected int $lastModifiedTime;

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
     * Site templates
     */
    protected Templates $templates;

    /**
     * Site users
     */
    protected Users $users;

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
    protected array $routeAliases = [];

    /**
     * Site files
     */
    protected FileCollection $files;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected App $app,
        protected Config $config,
        protected LanguagesFactory $languagesFactory,
        protected PageFactory $pageFactory,
        protected PageCollectionFactory $pageCollectionFactory,
    ) {
        $this->setMultiple($data);
    }

    public function __toString(): string
    {
        return (string) $this->title();
    }

    public function site(): Site
    {
        return $this;
    }

    public function parent(): Page|Site|null
    {
        return $this->parent ??= null;
    }

    public function siblings(): PageCollection
    {
        return $this->siblings ??= $this->pageCollectionFactory->make([]);
    }

    public function inclusiveSiblings(): PageCollection
    {
        return $this->inclusiveSiblings ??= $this->pageCollectionFactory->make([$this->route() ?? '' => $this]);
    }

    /**
     * Get site path
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Get site filename
     */
    public function contentFile(): ?ContentFile
    {
        return $this->contentFile;
    }

    public function contentPath(): ?string
    {
        return $this->contentPath;
    }

    public function contentRelativePath(): ?string
    {
        return '/';
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
     * Get site templates
     */
    public function templates(): Templates
    {
        return $this->templates;
    }

    /**
     * Get site users
     */
    public function users(): Users
    {
        return $this->users;
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
            'charset'   => $this->config->get('system.charset'),
            'generator' => $this->config->get('system.metadata.setGenerator') ? 'Formwork' : null,
        ];

        $data = array_filter([...$defaults, ...$this->data['metadata']]);

        return $this->metadata = new MetadataCollection($data);
    }

    /**
     * Get site last modified time
     */
    public function lastModifiedTime(): ?int
    {
        if ($this->contentPath === null) {
            return null;
        }
        return $this->lastModifiedTime = FileSystem::lastModifiedTime($this->contentPath);
    }

    /**
     * Return whether site has been modified since given time
     */
    public function modifiedSince(int $time): bool
    {
        if ($this->contentPath === null) {
            return false;
        }
        return FileSystem::directoryModifiedSince($this->contentPath, $time);
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
        return $this->storage[$path] ?? ($this->storage[$path] = $this->pageFactory->make(['site' => $this, 'path' => $path]));
    }

    /**
     * Retrieve all pages from the given directory
     */
    public function retrievePages(string $path, bool $recursive = false): PageCollection
    {
        /**
         * @var array<string, Page>
         */
        $pages = [];

        foreach (FileSystem::listDirectories($path, includeEmpty: false) as $dir) {
            $pagePath = FileSystem::joinPaths($path, $dir, '/');

            if ($dir[0] !== '_') {
                $page = $this->retrievePage($pagePath);

                if ($page->hasContentFile()) {
                    $pages[$page->route() ?? ''] = $page;
                }

                if ($recursive) {
                    $pages = [...$pages, ...$this->retrievePages($pagePath, recursive: true)->toArray()];
                }
            }
        }

        $pageCollection = $this->pageCollectionFactory->make($pages);

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
        $path = $this->contentPath;

        if ($path === null) {
            return null;
        }

        foreach ($components as $component) {
            $found = false;
            foreach (FileSystem::listDirectories($path, includeEmpty: false) as $dir) {
                if (preg_replace(Page::NUM_REGEX, '', $dir) === $component) {
                    $path .= FileSystem::joinPaths($dir, '/');
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return null;
            }
        }

        return $this->retrievePage($path);
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
     *
     * @throws PageNotFoundException If the site index page cannot be found
     */
    public function indexPage(): Page
    {
        return $this->findPage($this->config->get('system.pages.index'))
            ?? throw new PageNotFoundException('Site index page not found');
    }

    /**
     * Return or render site error page
     *
     * @throws PageNotFoundException If the site error page cannot be found
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

    /**
     * Return defined schemes
     */
    public function schemes(): Schemes
    {
        return $this->app->schemes();
    }

    /**
     * Load site dependencies
     */
    public function load(): void
    {
        $this->scheme = $this->app->schemes()->get('config.site');
        $this->templates = $this->app->getService(Templates::class);
        $this->users = $this->app->getService(Users::class);

        $this->fields = $this->scheme->fields();
        $this->fields->setModel($this);

        $this->fields->setValues($this->data);
    }

    /**
     * Get site files
     */
    public function files(): FileCollection
    {
        if (isset($this->files)) {
            return $this->files;
        }

        $files = [];

        $path = $this->config->get('system.files.paths.site');

        foreach (FileSystem::listFiles($path) as $file) {
            $extension = '.' . FileSystem::extension($file);
            if (Str::endsWith($file, $this->config->get('system.files.metadataExtension'))) {
                continue;
            }
            if (in_array($extension, $this->config->get('system.files.allowedExtensions'), true)) {
                $files[] = $this->app->getService(FileFactory::class)->make(FileSystem::joinPaths($path, $file));
            }
        }

        return $this->files = new FileCollection($files);
    }

    /**
     * Set languages
     *
     * @param array{available: list<string>, httpPreferred: bool, default?: string} $config
     */
    protected function setLanguages(array $config): void
    {
        $this->data['languages'] = $config;
        $this->languages = $this->languagesFactory->make($config);

        if (($currentTranslation = $this->languages->current() ?? $this->languages->default()) !== null) {
            $this->app->translations()->setCurrent($currentTranslation->code());
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function setMetadata(array $metadata): void
    {
        $this->data['metadata'] = $metadata;
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

    /**
     * Set site path
     */
    protected function setPath(string $path): void
    {
        $this->path = $this->data['path'] = $path;
    }

    /**
     * Set site content path
     */
    protected function setContentPath(string $path): void
    {
        $this->contentPath = FileSystem::normalizePath($path . '/');

        $this->route = '/';

        $this->slug = '';
    }

    /**
     * Load site aliases
     *
     * @param array<string, string> $aliases
     */
    protected function setRouteAliases(array $aliases): void
    {
        foreach ($aliases as $from => $to) {
            $this->routeAliases[trim((string) $from, '/')] = Str::wrap((string) $to, '/');
        }
        $this->data['routeAliases'] = $this->routeAliases;
    }
}
