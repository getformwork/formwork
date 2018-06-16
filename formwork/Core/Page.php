<?php

namespace Formwork\Core;
use Formwork\Data\DataGetter;
use Formwork\Files\Files;
use Formwork\Parsers\ParsedownExtension as Parsedown;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;
use Formwork\Utils\Uri;
use Exception;
use Spyc;

class Page {

    protected static $contentParser;

    protected $path;

    protected $uri;

    protected $slug;

    protected $id;

    protected $filename;

    protected $template;

    protected $files = array();

    protected $data = array();

    protected $parents;

    protected $children;

    protected $descendants;

    protected $siblings;

    public function __construct($path) {
        if (is_null(static::$contentParser)) static::$contentParser = new Parsedown();
        $this->path = FileSystem::normalize($path);
        $relativePath = substr($this->path, strlen(Formwork::instance()->option('content.path')) - 1);
        $this->slug = Uri::normalize(preg_replace('~/(\d+-)~', '/', $relativePath));
        $this->uri = HTTPRequest::root() . ltrim($this->slug, '/');
        $this->id = FileSystem::basename($this->path);
        $this->loadFiles();
        if (!$this->empty()) {
            $this->parse();
            $this->processData();
        }
    }

    protected function contentParser() {
        static::$contentParser->setPage($this);
        return static::$contentParser;
    }

    protected function loadFiles() {
        $files = array();
        foreach (FileSystem::listFiles($this->path) as $file) {
            $name = FileSystem::name($file);
            $extension = '.' . FileSystem::extension($file);
            if (is_null($this->filename) && Formwork::instance()->site()->hasTemplate($name)) {
                $this->filename = $file;
                $this->template = new Template($name);
            } elseif (in_array($extension, Formwork::instance()->option('files.allowed_extensions'))) {
                $files[] = $file;
            }
        }
        $this->files = new Files($files, $this->path);
    }

    public function reload() {
        $vars = array('uri', 'parents', 'children', 'descendants', 'siblings');
        foreach ($vars as $var) $this->$var = null;
        $this->__construct($this->path);
    }

    public function images() {
        return $this->files()->filterByType('image');
    }

    protected function parse() {
        $contents = FileSystem::read($this->path . DS . $this->filename);
        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(?:(.+?)\s+={3}\s+)?(.*?)\s*$/s', $contents, $matches)) {
            throw new Exception('Invalid page format');
        }
        list($match, $frontmatter, $summary, $body) = $matches;
        $this->rawContent = str_replace("\r\n", "\n", empty($summary) ? $body : $summary . "\n\n===\n\n" . $body);
        $this->frontmatter = Spyc::YAMLLoadString($frontmatter);
        $this->data = array_merge($this->defaults(), $this->frontmatter);
        if (!empty($summary)) $this->summary = $this->contentParser()->text($summary);
        $this->content = $this->contentParser()->text($body);
    }

    public function defaults() {
        return array(
            'published'    => true,
            'routable'     => true,
            'searchable'   => true,
            'cacheable'    => true
        );
    }

    protected function processData() {
        $this->data['visible'] = (bool) preg_match('/^\d+-[\w-]+$/', $this->id);

        if ($this->published() && $this->has('publish-date')) {
            $this->data['published'] = (strtotime($this->get('publish-date')) < time());
        }
        if ($this->published() && $this->has('unpublish-date')) {
            $this->data['published'] = (strtotime($this->get('unpublish-date')) > time());
        }

        // If the page isn't published, it won't also be visible
        if (!$this->published()) $this->data['visible'] = false;

        // Prepends the page uri if image uri doesn't start with "/", which means it isn't relative to the root
        if ($this->has('image') && $this->image()[0] != '/') {
            $this->data['image'] = $this->uri() . $this->image();
        }
    }

    public function num() {
        preg_match('/^(\d+)-/', $this->id, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    public function isSite() {
        return false;
    }

    public function isIndexPage() {
        return trim($this->slug(), '/') == Formwork::instance()->option('pages.index');
    }

    public function isErrorPage() {
        return trim($this->slug(), '/') == Formwork::instance()->option('pages.error');
    }

    public function isDeletable() {
        if ($this->hasChildren() || $this->isSite() || $this->isIndexPage() || $this->isErrorPage()) return false;
        return true;
    }

    public function empty() {
        return is_null($this->filename);
    }

    public function relativePath() {
        $parentPath = FileSystem::dirname(Formwork::instance()->option('content.path'));
        return $parentPath == '.' ? DS . $this->path : substr($this->path, strlen($parentPath));
    }

    public function uri() {
        return $this->uri;
    }

    public function lastModifiedTime() {
        return FileSystem::lastModifiedTime($this->path . DS . $this->filename);
    }

    public function absoluteUri() {
        return Uri::make(array('host' => Uri::host()), $this->uri());
    }

    public function current() {
        return $this->uri() == Uri::normalize(Uri::path());
    }

    public function file($file) {
        return $this->files()->has($file) ? substr($this->relativePath() . $file, 1) : false;
    }

    public function parent() {
        $parentPath = FileSystem::dirname($this->path) . DS;
        if (FileSystem::isDirectory($parentPath) && $parentPath !== Formwork::instance()->option('content.path')) {
            if (isset(Site::$storage[$parentPath])) return Site::$storage[$parentPath];
            return Site::$storage[$parentPath] = new Page($parentPath);
        }
        // If no parent was found returns the site as first level pages' parent
        return Formwork::instance()->site();
    }

    public function parents() {
        if (!is_null($this->parents)) return $this->parents;
        $parentPages = array();
        $page = $this;
        while (($parent = $page->parent()) !== null) {
            $parentPages[] = $parent;
            $page = $parent;
        }
        $this->parents = new PageCollection(array_reverse($parentPages));
        return $this->parents;
    }

    public function children() {
        if (!is_null($this->children)) return $this->children;
        $pageCollection = PageCollection::fromPath($this->path);
        $this->children = $pageCollection;
        return $this->children;
    }

    public function hasChildren() {
        return !$this->children()->empty();
    }

    public function descendants() {
        if (!is_null($this->descendants)) return $this->descendants;
        $pageCollection = PageCollection::fromPath($this->path, true);
        $this->descendants = $pageCollection;
        return $this->descendants;
    }

    public function hasDescendants() {
        foreach ($this->children() as $child) {
            if ($child->hasChildren()) return true;
        }
        return false;
    }

    public function siblings() {
        if (!is_null($this->siblings)) return $this->siblings;
        $parentPath = FileSystem::dirname($this->path) . DS;
        $pageCollection = PageCollection::fromPath($parentPath);
        $pageCollection = $pageCollection->remove($this);
        $this->siblings = $pageCollection;
        return $this->siblings;
    }

    public function hasSiblings() {
        return !$this->siblings()->empty();
    }

    public function level() {
        return $this->parents()->count();
    }

    public function date($format = null) {
        if (is_null($format)) $format = Formwork::instance()->option('date.format');
        if ($this->has('publish-date')) {
            return date($format, strtotime($this->data['publish-date']));
        }
        return date($format, $this->lastModifiedTime());
    }

    public function renderToString($vars = array()) {
        return $this->template()->renderPage($this, $vars, true);
    }

    public function render($vars = array()) {
        echo $renderedPage = $this->renderToString($vars);
        return $renderedPage;
    }

    public function get($key, $default = null) {
		if (isset($this->$key)) return $this->$key;
        if (method_exists($this, $key)) return $this->$key();
		return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
	}

	public function has($key) {
		return isset($this->$key) || array_key_exists($key, $this->data);
	}

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function toArray() {
        return array(
            'title' => $this->title(),
            'uri' => $this->uri(),
            'template' => $this->template(),
            'num' => $this->num(),
            'data' => $this->data()
        );
    }

    public function __call($name, $arguments) {
        if (property_exists($this, $name)) return $this->$name;
        if ($this->has($name)) return $this->get($name);
        throw new Exception('Invalid method');
    }

    public function __toString() {
        return $this->id();
    }

    public function __debugInfo() {
        return $this->toArray();
    }

}
