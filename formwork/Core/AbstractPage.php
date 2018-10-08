<?php

namespace Formwork\Core;

use Formwork\Utils\FileSystem;
use LogicException;

abstract class AbstractPage
{
    protected $path;

    protected $uri;

    protected $data = array();

    protected $parents;

    protected $children;

    protected $descendants;

    public function uri($path = null)
    {
        if (is_null($path)) {
            return $this->uri;
        }
        return $this->uri . ltrim($path, '/');
    }

    public function lastModifiedTime()
    {
        return FileSystem::lastModifiedTime($this->path);
    }

    public function date($format = null)
    {
        if (is_null($format)) {
            $format = Formwork::instance()->option('date.format');
        }
        return date($format, $this->lastModifiedTime());
    }

    public function parent()
    {
        $parentPath = FileSystem::dirname($this->path) . DS;
        if (FileSystem::isDirectory($parentPath) && $parentPath !== Formwork::instance()->option('content.path')) {
            if (isset(Site::$storage[$parentPath])) {
                return Site::$storage[$parentPath];
            }
            return Site::$storage[$parentPath] = new Page($parentPath);
        }
        // If no parent was found returns the site as first level pages' parent
        return Formwork::instance()->site();
    }

    public function parents()
    {
        if (!is_null($this->parents)) {
            return $this->parents;
        }
        $parentPages = array();
        $page = $this;
        while (($parent = $page->parent()) !== null) {
            $parentPages[] = $parent;
            $page = $parent;
        }
        $this->parents = new PageCollection(array_reverse($parentPages));
        return $this->parents;
    }

    public function hasParents()
    {
        return !$this->parents()->isEmpty();
    }

    public function children()
    {
        if (!is_null($this->children)) {
            return $this->children;
        }
        $pageCollection = PageCollection::fromPath($this->path);
        $this->children = $pageCollection;
        return $this->children;
    }

    public function hasChildren()
    {
        return !$this->children()->isEmpty();
    }

    public function descendants()
    {
        if (!is_null($this->descendants)) {
            return $this->descendants;
        }
        $pageCollection = PageCollection::fromPath($this->path, true);
        $this->descendants = $pageCollection;
        return $this->descendants;
    }

    public function hasDescendants()
    {
        foreach ($this->children() as $child) {
            if ($child->hasChildren()) {
                return true;
            }
        }
        return false;
    }

    public function level()
    {
        return $this->parents()->count();
    }

    abstract public function isSite();

    abstract public function isIndexPage();

    abstract public function isErrorPage();

    abstract public function isDeletable();

    public function get($key, $default = null)
    {
        if (isset($this->$key)) {
            return $this->$key;
        }
        if (method_exists($this, $key)) {
            return $this->$key();
        }
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has($key)
    {
        return isset($this->$key) || array_key_exists($key, $this->data);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        if ($this->has($name)) {
            return $this->get($name);
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
