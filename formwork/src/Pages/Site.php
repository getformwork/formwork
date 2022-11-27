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
use Formwork\Utils\FileSystem;

class Site implements Arrayable
{
    use PageData;
    use PageTraversal;
    use PageUid;
    use PageUri;

    protected string $relativePath;

    protected string $route;

    protected int $lastModifiedTime;

    protected array $storage = [];

    protected ?Page $currentPage = null;

    protected Languages $languages;

    protected Scheme $scheme;

    protected FieldCollection $fields;

    protected TemplateCollection $templates;

    protected array $aliases;

    protected MetadataCollection $metadata;

    /**
     * Create a new Site instance
     */
    public function __construct(array $data)
    {
        $this->path = FileSystem::normalizePath(Formwork::instance()->config()->get('content.path'));

        $this->relativePath = DS;

        $this->route = '/';

        $this->scheme = Formwork::instance()->schemes()->get('config', 'site');

        $this->data = array_replace_recursive($this->defaults(), $data);

        $this->fields = $this->scheme->fields()->validate($this->data);

        $this->templates = TemplateCollection::fromPath(Formwork::instance()->config()->get('templates.path'));

        $this->loadAliases();
    }

    /**
     * Return site default data
     */
    public function defaults(): array
    {
        // Formwork::instance()->schemes()->get('config', 'site')->fields();
        return [
            'title'     => 'Formwork',
            'aliases'   => [],
            'metadata'  => [],
            'canonical' => null
        ];
    }

    public function lastModifiedTime(): int
    {
        if (isset($this->lastModifiedTime)) {
            return $this->lastModifiedTime;
        }
        return $this->lastModifiedTime = FileSystem::lastModifiedTime($this->path);
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
        return $this->storage[$path] = new Page($path);
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

        return !$page->isEmpty() ? $page : null;
    }

    /**
     * Set and return site current page
     */
    public function setCurrentPage(Page $page): Page
    {
        return $this->currentPage = $page;
    }

    /**
     * Set site languages
     */
    public function setLanguages(Languages $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * Return alias of a given route
     */
    public function resolveAlias(string $route): ?string
    {
        return $this->aliases[$route] ?? null;
    }

    public function metadata(): MetadataCollection
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $defaults = [
            'charset'     => Formwork::instance()->config()->get('charset'),
            'author'      => $this->get('author'),
            'description' => $this->get('description'),
            'generator'   => 'Formwork'
        ];

        $data = array_filter(array_merge($defaults, $this->data['metadata']));

        if (!Formwork::instance()->config()->get('metadata.set_generator')) {
            unset($data['generator']);
        }

        return $this->metadata = new MetadataCollection($data);
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

    public function isSite(): bool
    {
        return true;
    }

    public function isIndexPage(): bool
    {
        return false;
    }

    public function isErrorPage(): bool
    {
        return false;
    }

    public function isDeletable(): bool
    {
        return false;
    }

    protected function loadAliases(): void
    {
        foreach ($this->data['aliases'] as $from => $to) {
            $this->aliases[trim($from, '/')] = trim($to, '/');
        }
    }

    public function __toString()
    {
        return $this->title();
    }
}
