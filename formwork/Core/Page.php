<?php

namespace Formwork\Core;

use Formwork\Files\Files;
use Formwork\Parsers\ParsedownExtension as Parsedown;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use RuntimeException;

class Page extends AbstractPage
{
    const NUM_REGEX = '/^(\d+)-/';
    const PAGE_STATUS_PUBLISHED = 'published';
    const PAGE_STATUS_NOT_PUBLISHED = 'not-published';
    const PAGE_STATUS_NOT_ROUTABLE = 'not-routable';

    protected static $contentParser;

    protected $slug;

    protected $id;

    protected $filename;

    protected $template;

    protected $files = array();

    protected $siblings;

    public function __construct($path)
    {
        if (is_null(static::$contentParser)) {
            static::$contentParser = new Parsedown();
        }
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

    public function defaults()
    {
        return array(
            'published'  => true,
            'routable'   => true,
            'searchable' => true,
            'cacheable'  => true,
            'sortable'   => true
        );
    }

    public function reload()
    {
        $vars = array('uri', 'parents', 'children', 'descendants', 'siblings');
        foreach ($vars as $var) {
            $this->$var = null;
        }
        $this->__construct($this->path);
    }

    public function empty()
    {
        return is_null($this->filename);
    }

    public function lastModifiedTime()
    {
        $pathLastModifiedTime = parent::lastModifiedTime();
        $fileLastModifiedTime = FileSystem::lastModifiedTime($this->path . $this->filename);
        return $fileLastModifiedTime > $pathLastModifiedTime ? $fileLastModifiedTime : $pathLastModifiedTime;
    }

    public function relativePath()
    {
        $parentPath = FileSystem::dirname(Formwork::instance()->option('content.path'));
        return $parentPath == '.' ? DS . $this->path : substr($this->path, strlen($parentPath));
    }

    public function num()
    {
        preg_match(self::NUM_REGEX, $this->id, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    public function absoluteUri()
    {
        return Uri::make(array('host' => Uri::host()), $this->uri());
    }

    public function current()
    {
        return Formwork::instance()->site()->currentPage() === $this;
    }

    public function date($format = null)
    {
        if (is_null($format)) {
            $format = Formwork::instance()->option('date.format');
        }
        if ($this->has('publish-date')) {
            return date($format, strtotime($this->data['publish-date']));
        }
        return parent::date($format);
    }

    public function status()
    {
        if ($this->published()) {
            $status = self::PAGE_STATUS_PUBLISHED;
        }
        if (!$this->routable()) {
            $status = self::PAGE_STATUS_NOT_ROUTABLE;
        }
        if (!$this->published()) {
            $status = self::PAGE_STATUS_NOT_PUBLISHED;
        }
        return $status;
    }

    public function siblings()
    {
        if (!is_null($this->siblings)) {
            return $this->siblings;
        }
        $parentPath = FileSystem::dirname($this->path) . DS;
        $pageCollection = PageCollection::fromPath($parentPath);
        $pageCollection = $pageCollection->remove($this);
        $this->siblings = $pageCollection;
        return $this->siblings;
    }

    public function hasSiblings()
    {
        return !$this->siblings()->empty();
    }

    public function isSite()
    {
        return false;
    }

    public function isIndexPage()
    {
        return trim($this->slug(), '/') == Formwork::instance()->option('pages.index');
    }

    public function isErrorPage()
    {
        return trim($this->slug(), '/') == Formwork::instance()->option('pages.error');
    }

    public function isDeletable()
    {
        if ($this->hasChildren() || $this->isSite() || $this->isIndexPage() || $this->isErrorPage()) {
            return false;
        }
        return true;
    }

    public function file($file)
    {
        return $this->files()->has($file) ? substr($this->relativePath() . $file, 1) : false;
    }

    public function images()
    {
        return $this->files()->filterByType('image');
    }

    public function renderToString($vars = array())
    {
        return $this->template()->renderPage($this, $vars, true);
    }

    public function render($vars = array())
    {
        echo $renderedPage = $this->renderToString($vars);
        return $renderedPage;
    }

    public function toArray()
    {
        return array(
            'title' => $this->title(),
            'uri' => $this->uri(),
            'template' => $this->template()->name(),
            'num' => $this->num(),
            'data' => $this->data()
        );
    }

    protected function loadFiles()
    {
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

    protected function contentParser()
    {
        static::$contentParser->setPage($this);
        return static::$contentParser;
    }

    protected function parse()
    {
        $contents = FileSystem::read($this->path . DS . $this->filename);
        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(?:(.+?)\s+={3}\s+)?(.*?)\s*$/s', $contents, $matches)) {
            throw new RuntimeException('Invalid page format');
        }
        list($match, $frontmatter, $summary, $body) = $matches;
        $this->rawContent = str_replace("\r\n", "\n", empty($summary) ? $body : $summary . "\n\n===\n\n" . $body);
        $this->frontmatter = YAML::parse($frontmatter);
        $this->data = array_merge($this->defaults(), $this->frontmatter);
        if (!empty($summary)) {
            $this->summary = $this->contentParser()->text($summary);
        }
        $this->content = $this->contentParser()->text($body);
    }

    protected function processData()
    {
        $this->data['visible'] = !is_null($this->num());

        if ($this->published() && $this->has('publish-date')) {
            $this->data['published'] = (strtotime($this->get('publish-date')) < time());
        }
        if ($this->published() && $this->has('unpublish-date')) {
            $this->data['published'] = (strtotime($this->get('unpublish-date')) > time());
        }

        // If the page isn't published, it won't also be visible
        if (!$this->published()) {
            $this->data['visible'] = false;
        }

        if (is_null($this->num()) || $this->template()->scheme()->get('num') == 'date') {
            $this->data['sortable'] = false;
        }

        // Prepends the page uri if image uri doesn't start with '/', which means it isn't relative to the root
        if ($this->has('image') && $this->image()[0] != '/') {
            $this->data['image'] = $this->uri($this->image());
        }
    }

    public function __toString()
    {
        return $this->id();
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }
}
