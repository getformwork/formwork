<?php

namespace Formwork\Pages\Traits;

use Formwork\Formwork;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\Site;

trait PageTraversal
{
    protected string $path;

    protected Page|Site|null $parent;

    protected PageCollection $children;

    protected PageCollection $descendants;

    protected PageCollection $ancestors;

    protected PageCollection $siblings;

    protected PageCollection $inclusiveSiblings;

    abstract public function isSite(): bool;

    public function parent(): Page|Site|null
    {
        if (isset($this->parent)) {
            return $this->parent;
        }

        if ($this->isSite()) {
            return $this->parent = null;
        }

        $parentPath = dirname($this->path) . DS;

        if ($parentPath === Formwork::instance()->config()->get('content.path')) {
            return $this->parent = Formwork::instance()->site();
        }

        return $this->parent = Formwork::instance()->site()->retrievePage($parentPath);
    }

    public function hasParent(): bool
    {
        return $this->parent() !== null;
    }

    public function isParentOf(Page|Site $page): bool
    {
        return $page->parent() === $page;
    }

    public function children(): PageCollection
    {
        if (isset($this->children)) {
            return $this->children;
        }

        return $this->children = PageCollection::fromPath($this->path);
    }

    public function hasChildren(): bool
    {
        return !$this->children()->isEmpty();
    }

    public function isChildOf(Page|Site $page): bool
    {
        return $page->children()->contains($this);
    }

    public function descendants(): PageCollection
    {
        if (isset($this->descendants)) {
            return $this->descendants;
        }

        return $this->descendants = PageCollection::fromPath($this->path, recursive: true);
    }

    public function hasDescendants(): bool
    {
        return !$this->descendants()->isEmpty();
    }

    public function isDescendantOf(Page|Site $page): bool
    {
        return $page->descendants()->contains($this);
    }

    public function ancestors(): PageCollection
    {
        if (isset($this->ancestors)) {
            return $this->ancestors;
        }

        $ancestors = [];

        $page = $this;

        while (($parent = $page->parent()) !== null) {
            $ancestors[] = $parent;
            $page = $parent;
        }

        return $this->ancestors = new PageCollection($ancestors);
    }

    public function hasAncestors(): bool
    {
        return !$this->ancestors()->isEmpty();
    }

    public function isAncestorOf(Page|Site $page): bool
    {
        return $page->ancestors()->contains($this);
    }

    public function siblings(): PageCollection
    {
        if (isset($this->siblings)) {
            return $this->siblings;
        }

        if ($this->isSite()) {
            return $this->siblings = new PageCollection();
        }

        return $this->siblings = $this->inclusiveSiblings()->without($this);
    }

    public function inclusiveSiblings(): PageCollection
    {
        if (isset($this->inclusiveSiblings)) {
            return $this->inclusiveSiblings;
        }

        if ($this->isSite()) {
            return $this->inclusiveSiblings = new PageCollection([$this]);
        }

        return $this->inclusiveSiblings = $this->parent()->children();
    }

    public function hasSiblings(): bool
    {
        return !$this->siblings()->isEmpty();
    }

    public function isSiblingOf(Page|Site $page): bool
    {
        return !$page->siblings()->contains($this);
    }

    public function previousSibling(): ?Page
    {
        return $this->inclusiveSiblings()->nth($this->index() - 1);
    }

    public function nextSibling(): ?Page
    {
        return $this->inclusiveSiblings()->nth($this->index() + 1);
    }

    public function index(): int
    {
        return $this->inclusiveSiblings()->indexOf($this);
    }

    public function level(): int
    {
        return $this->ancestors()->count();
    }
}
