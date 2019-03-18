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
    /**
     * Page num regex
     *
     * @var string
     */
    const NUM_REGEX = '/^(\d+)-/';

    /**
     * Page 'published' status
     *
     * @var string
     */
    const PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page 'not published' status
     *
     * @var string
     */
    const PAGE_STATUS_NOT_PUBLISHED = 'not-published';

    /**
     * Page 'not routable' status
     *
     * @var string
     */
    const PAGE_STATUS_NOT_ROUTABLE = 'not-routable';

    /**
     * Page content parser instance
     *
     * @var Parsedown
     */
    protected static $contentParser;

    /**
     * Page id
     *
     * @var string
     */
    protected $id;

    /**
     * Page slug
     *
     * @var string
     */
    protected $slug;

    /**
     * Page filename
     *
     * @var string
     */
    protected $filename;

    /**
     * Page template
     *
     * @var Template
     */
    protected $template;

    /**
     * Page files
     *
     * @var Files
     */
    protected $files;

    /**
     * Page frontmatter
     *
     * @var array
     */
    protected $frontmatter = array();

    /**
     * Page unprocessed content
     *
     * @var string
     */
    protected $rawContent;

    /**
     * Page summary
     *
     * @var string
     */
    protected $summary;

    /**
     * Page content
     *
     * @var string
     */
    protected $content;

    /**
     * PageCollection containing page siblings
     *
     * @var PageCollection
     */
    protected $siblings;

    /**
     * Create a new Page instance
     *
     * @param string $path
     */
    public function __construct($path)
    {
        if (is_null(static::$contentParser)) {
            static::$contentParser = new Parsedown();
        }
        $this->path = FileSystem::normalize($path);
        $this->relativePath = substr($this->path, strlen(Formwork::instance()->option('content.path')) - 1);
        $this->route = Uri::normalize(preg_replace('~/(\d+-)~', '/', $this->relativePath));
        $this->uri = HTTPRequest::root() . ltrim($this->route, '/');
        $this->id = basename($this->path);
        $this->slug = basename($this->route);
        $this->loadFiles();
        if (!$this->isEmpty()) {
            $this->parse();
            $this->processData();
        }
    }

    /**
     * Return page default data
     *
     * @return array
     */
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

    /**
     * Reload page
     *
     * @return $this
     */
    public function reload()
    {
        $vars = array('uri', 'filename', 'template', 'parents', 'children', 'descendants', 'siblings');
        foreach ($vars as $var) {
            $this->$var = null;
        }
        $this->__construct($this->path);
    }

    /**
     * Return whether page is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return is_null($this->filename);
    }

    /**
     * @inheritdoc
     */
    public function lastModifiedTime()
    {
        $pathLastModifiedTime = parent::lastModifiedTime();
        $fileLastModifiedTime = FileSystem::lastModifiedTime($this->path . $this->filename);
        return $fileLastModifiedTime > $pathLastModifiedTime ? $fileLastModifiedTime : $pathLastModifiedTime;
    }

    /**
     * Get page num
     *
     * @return int|null
     */
    public function num()
    {
        preg_match(self::NUM_REGEX, $this->id, $matches);
        return isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Get page absolute URI
     *
     * @return string
     */
    public function absoluteUri()
    {
        return Uri::make(array('host' => Uri::host()), $this->uri());
    }

    /**
     * Return whether this is the currently active page
     *
     * @return bool
     */
    public function isCurrent()
    {
        return Formwork::instance()->site()->currentPage() === $this;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * Get page status
     *
     * @return string
     */
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

    /**
     * Return a PageCollection containing page siblings
     *
     * @return PageCollection
     */
    public function siblings()
    {
        if (!is_null($this->siblings)) {
            return $this->siblings;
        }
        $parentPath = dirname($this->path) . DS;
        $pageCollection = PageCollection::fromPath($parentPath);
        $pageCollection = $pageCollection->remove($this);
        $this->siblings = $pageCollection;
        return $this->siblings;
    }

    /**
     * Return whether page has siblings
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return !$this->siblings()->isEmpty();
    }

    /**
     * @inheritdoc
     */
    public function isSite()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isIndexPage()
    {
        return trim($this->route(), '/') === Formwork::instance()->option('pages.index');
    }

    /**
     * @inheritdoc
     */
    public function isErrorPage()
    {
        return trim($this->route(), '/') === Formwork::instance()->option('pages.error');
    }

    /**
     * @inheritdoc
     */
    public function isDeletable()
    {
        if ($this->hasChildren() || $this->isSite() || $this->isIndexPage() || $this->isErrorPage()) {
            return false;
        }
        return true;
    }

    /**
     * Return a file path relative to Formwork root
     *
     * @param string $file Name of the file
     *
     * @return string|null File path or null if file is not found
     */
    public function file($file)
    {
        return $this->files()->has($file) ? substr($this->files()->get($file)->path(), strlen(ROOT_PATH)) : null;
    }

    /**
     * Return a Files collection containing only images
     *
     * @return Files
     */
    public function images()
    {
        return $this->files()->filterByType('image');
    }

    /**
     * Render page to string
     *
     * @param array $vars Variables to pass to the page
     *
     * @return string
     */
    public function renderToString($vars = array())
    {
        return $this->template()->renderPage($this, $vars, true);
    }

    /**
     * Render page and return rendered content
     *
     * @param array $vars Variables to pass to the page
     *
     * @return string
     */
    public function render($vars = array())
    {
        echo $renderedPage = $this->renderToString($vars);
        return $renderedPage;
    }

    /**
     * Return an array containing page data
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'route'    => $this->route(),
            'uri'      => $this->uri(),
            'id'       => $this->id(),
            'slug'     => $this->slug(),
            'template' => $this->template()->name(),
            'num'      => $this->num(),
            'data'     => $this->data()
        );
    }

    /**
     * Load files related to page
     */
    protected function loadFiles()
    {
        $files = array();
        foreach (FileSystem::listFiles($this->path) as $file) {
            $name = FileSystem::name($file);
            $extension = '.' . FileSystem::extension($file);
            if (is_null($this->filename) && Formwork::instance()->site()->hasTemplate($name)) {
                $this->filename = $file;
                $this->template = new Template($name);
            } elseif (in_array($extension, Formwork::instance()->option('files.allowed_extensions'), true)) {
                $files[] = $file;
            }
        }
        $this->files = new Files($files, $this->path);
    }

    /**
     * Initialize content parser
     *
     * @return Parsedown
     */
    protected function contentParser()
    {
        static::$contentParser->setPage($this);
        return static::$contentParser;
    }

    /**
     * Parse page content
     */
    protected function parse()
    {
        $contents = FileSystem::read($this->path . $this->filename);
        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(?:(.+?)\s+={3}\s+)?(.*?)\s*$/s', $contents, $matches)) {
            throw new RuntimeException('Invalid page format');
        }
        list($match, $frontmatter, $summary, $body) = $matches;
        $this->frontmatter = YAML::parse($frontmatter);
        $this->rawContent = str_replace("\r\n", "\n", empty($summary) ? $body : $summary . "\n\n===\n\n" . $body);
        $this->data = array_merge($this->defaults(), $this->frontmatter);
        if (!empty($summary)) {
            $this->summary = $this->contentParser()->text($summary);
        }
        $this->content = $this->contentParser()->text($body);
    }

    /**
     * Process page data
     */
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

        if (is_null($this->num()) || $this->template()->scheme()->get('num') === 'date') {
            $this->data['sortable'] = false;
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
