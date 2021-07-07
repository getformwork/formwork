<?php

namespace Formwork;

use Formwork\Metadata\Metadata;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use BadMethodCallException;

abstract class AbstractPage
{
    /**
     * Page path
     */
    protected string $path;

    /**
     * Page path relative to content path
     */
    protected string $relativePath;

    /**
     * Page unique identifier
     */
    protected string $uid;

    /**
     * Page route
     */
    protected string $route;

    /**
     * Page data
     */
    protected array $data = [];

    /**
     * Page uri
     */
    protected string $uri;

    /**
     * Page absolute uri
     */
    protected string $absoluteUri;

    /**
     * Page last modified time
     */
    protected int $lastModifiedTime;

    /**
     * Page modified date
     */
    protected string $date;

    /**
     * Page metadata
     */
    protected Metadata $metadata;

    /**
     * Page parent
     *
     * @var Page|Site
     */
    protected $parent;

    /**
     * PageCollection containing page parents
     */
    protected PageCollection $parents;

    /**
     * Page level
     */
    protected int $level;

    /**
     * PageCollection containing page children
     */
    protected PageCollection $children;

    /**
     * PageCollection containing page descendants
     */
    protected PageCollection $descendants;

    /**
     * Return a URI relative to page
     *
     * @param bool|string $includeLanguage
     */
    public function uri(string $path = '', $includeLanguage = true): string
    {
        $base = HTTPRequest::root();
        if ($includeLanguage) {
            $language = is_string($includeLanguage) ? $includeLanguage : Formwork::instance()->site()->languages()->current();

            $default = Formwork::instance()->site()->languages()->default();
            $preferred = Formwork::instance()->site()->languages()->preferred();

            if (($language !== null && $language !== $default) || ($preferred !== null && $preferred !== $default)) {
                $base .= $language . '/';
            }
        }
        return $base . ltrim($this->route, '/') . ltrim($path, '/');
    }

    /**
     * Get the page unique identifier
     */
    public function uid(): string
    {
        if (isset($this->uid)) {
            return $this->uid;
        }
        return $this->uid = substr(hash('sha256', $this->relativePath), 0, 32);
    }

    /**
     * Get page absolute URI
     */
    public function absoluteUri(): string
    {
        if (isset($this->absoluteUri)) {
            return $this->absoluteUri;
        }
        return $this->absoluteUri = Uri::resolveRelative($this->uri());
    }

    /**
     * Get page last modified time
     */
    public function lastModifiedTime(): int
    {
        if (isset($this->lastModifiedTime)) {
            return $this->lastModifiedTime;
        }
        return $this->lastModifiedTime = FileSystem::lastModifiedTime($this->path);
    }

    /**
     * Return page date optionally in a given format
     *
     * @param string $format
     */
    public function date(string $format = null): string
    {
        if ($format === null) {
            $format = Formwork::instance()->config()->get('date.format');
        }
        return date($format, $this->lastModifiedTime());
    }

    /**
     * Get parent page
     *
     * @return Page|Site|null
     */
    public function parent()
    {
        if (isset($this->parent)) {
            return $this->parent;
        }
        $parentPath = dirname($this->path) . DS;
        if (FileSystem::isDirectory($parentPath) && $parentPath !== Formwork::instance()->config()->get('content.path')) {
            return $this->parent = Formwork::instance()->site()->retrievePage($parentPath);
        }
        // If no parent was found returns the site as first level pages' parent
        return $this->parent = Formwork::instance()->site();
    }

    /**
     * Return a PageCollection containing page parents
     */
    public function parents(): PageCollection
    {
        if (isset($this->parents)) {
            return $this->parents;
        }
        $parentPages = [];
        $page = $this;
        while (($parent = $page->parent()) !== null) {
            $parentPages[] = $parent;
            $page = $parent;
        }
        return $this->parents = new PageCollection(array_reverse($parentPages));
    }

    /**
     * Return whether page has parents
     */
    public function hasParents(): bool
    {
        return !$this->parents()->isEmpty();
    }

    /**
     * Return a PageCollection containing page children
     */
    public function children(): PageCollection
    {
        if (isset($this->children)) {
            return $this->children;
        }
        return $this->children = PageCollection::fromPath($this->path);
    }

    /**
     * Return whether page has children
     */
    public function hasChildren(): bool
    {
        return !$this->children()->isEmpty();
    }

    /**
     * Return a PageCollection containing page descendants
     */
    public function descendants(): PageCollection
    {
        if (isset($this->descendants)) {
            return $this->descendants;
        }
        return $this->descendants = PageCollection::fromPath($this->path, true);
    }

    /**
     * Return whether page has descendants
     */
    public function hasDescendants(): bool
    {
        foreach ($this->children() as $child) {
            if ($child->hasChildren()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return page level
     */
    public function level(): int
    {
        if (isset($this->level)) {
            return $this->level;
        }
        return $this->level = $this->parents()->count();
    }

    /**
     * Return whether current page is Site
     */
    abstract public function isSite(): bool;

    /**
     * Return whether current page is index page
     */
    abstract public function isIndexPage(): bool;

    /**
     * Return whether current page is error page
     */
    abstract public function isErrorPage(): bool;

    /**
     * Return whether current page is deletable
     */
    abstract public function isDeletable(): bool;

    /**
     * Return page metadata
     */
    abstract public function metadata(): Metadata;

    /**
     * Get page data by key
     */
    public function get(string $key, $default = null)
    {
        if (property_exists($this, $key)) {
            // Call getter method if exists and property is null
            if (!isset($this->$key) && method_exists($this, $key)) {
                return $this->$key();
            }
            return $this->$key;
        }
        if (Arr::has($this->data, $key)) {
            return Arr::get($this->data, $key, $default);
        }
        return $default;
    }

    /**
     * Return whether page data has a key
     */
    public function has(string $key): bool
    {
        return property_exists($this, $key) || Arr::has($this->data, $key);
    }

    /**
     * Set page data
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->data, $key, $value);
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }
}
