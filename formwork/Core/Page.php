<?php

namespace Formwork\Core;

use Formwork\Files\Files;
use Formwork\Parsers\ParsedownExtension as Parsedown;
use Formwork\Parsers\YAML;
use Formwork\Template\Template;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\Uri;
use RuntimeException;

class Page extends AbstractPage
{
    /**
     * Page num regex
     *
     * @var string
     */
    public const NUM_REGEX = '/^(\d+)-/';

    /**
     * Page 'published' status
     *
     * @var string
     */
    public const PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page 'not published' status
     *
     * @var string
     */
    public const PAGE_STATUS_NOT_PUBLISHED = 'not-published';

    /**
     * Page 'not routable' status
     *
     * @var string
     */
    public const PAGE_STATUS_NOT_ROUTABLE = 'not-routable';

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
     * Page language
     *
     * @var string
     */
    protected $language;

    /**
     * Available page languages
     *
     * @var array
     */
    protected $availableLanguages = array();

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
     * Whether page is published
     *
     * @var bool
     */
    protected $published;

    /**
     * Whether page is routable
     *
     * @var bool
     */
    protected $routable;

    /**
     * Whether page is visible
     *
     * @var bool
     */
    protected $visible;

    /**
     * Whether page is sortable
     *
     * @var bool
     */
    protected $sortable;

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
        $this->route = Uri::normalize(preg_replace('~/(\d+-)~', '/', strtr($this->relativePath, DS, '/')));
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
            'visible'    => !is_null($this->num()),
            'searchable' => true,
            'cacheable'  => true,
            'sortable'   => true,
            'headers'    => array(),
            'metadata'   => array()
        );
    }

    /**
     * Reload page
     *
     * @return $this
     */
    public function reload()
    {
        $vars = array('filename', 'template', 'parents', 'children', 'descendants', 'siblings');
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
        return $this->siblings = PageCollection::fromPath($parentPath)->remove($this);
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
     * @inheritdoc
     */
    public function metadata()
    {
        if (!is_null($this->metadata)) {
            return $this->metadata;
        }
        $metadata = clone Formwork::instance()->site()->metadata();
        $metadata->setMultiple($this->data['metadata']);
        return $this->metadata = $metadata;
    }

    /**
     * Return whether page has a language
     *
     * @param string $language
     *
     * @return bool
     */
    public function hasLanguage($language)
    {
        return in_array($language, $this->availableLanguages, true);
    }

    /**
     * Set page language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        if (!$this->hasLanguage($language)) {
            throw new RuntimeException('Invalid page language "' . $language . '"');
        }
        $this->language = $language;
        $this->__construct($this->path);
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
    public function renderToString(array $vars = array())
    {
        return $this->template()->render($vars, true);
    }

    /**
     * Render page and return rendered content
     *
     * @param array $vars        Variables to pass to the page
     * @param bool  $sendHeaders Whether to send headers before rendering
     *
     * @return string
     */
    public function render(array $vars = array(), $sendHeaders = true)
    {
        if ($sendHeaders) {
            $this->sendHeaders();
        }
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
        $contentFiles = array();
        $files = array();

        foreach (FileSystem::listFiles($this->path) as $file) {
            $name = FileSystem::name($file);
            $extension = '.' . FileSystem::extension($file);
            if ($extension === Formwork::instance()->option('content.extension')) {
                $language = null;
                if (preg_match('/([a-z0-9]+)\.([a-z]+)/', $name, $matches)) {
                    // Parse double extension
                    list($match, $name, $language) = $matches;
                }
                if (Formwork::instance()->site()->hasTemplate($name)) {
                    $contentFiles[$language] = array(
                        'filename' => $file,
                        'template' => $name
                    );
                    if (!is_null($language) && !in_array($language, $this->availableLanguages, true)) {
                        $this->availableLanguages[] = $language;
                    }
                }
            } elseif (in_array($extension, Formwork::instance()->option('files.allowed_extensions'), true)) {
                $files[] = $file;
            }
        }

        if (!empty($contentFiles)) {
            // Get correct content file based on current language
            ksort($contentFiles);
            $currentLanguage = $this->language ?: Formwork::instance()->site()->languages()->current();
            $key = isset($contentFiles[$currentLanguage]) ? $currentLanguage : array_keys($contentFiles)[0];

            // Set actual language
            $this->language = $key ?: null;

            $this->filename = $contentFiles[$key]['filename'];
            $this->template = new Template($contentFiles[$key]['template'], $this);
        }

        $this->files = Files::fromPath($this->path, $files);
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
        $this->content = $this->summary . $this->contentParser()->text($body);
    }

    /**
     * Process page data
     */
    protected function processData()
    {
        $this->published = $this->data['published'];

        if ($this->has('publish-date')) {
            $this->published = $this->published && strtotime($this->get('publish-date')) < time();
        }

        if ($this->has('unpublish-date')) {
            $this->published = $this->published && strtotime($this->get('unpublish-date')) > time();
        }

        $this->routable = $this->data['routable'];

        $this->visible = $this->data['visible'];

        // If the page isn't published, it won't also be visible
        if (!$this->published) {
            $this->visible = false;
        }

        $this->sortable = $this->data['sortable'];

        if (is_null($this->num()) || $this->template()->scheme()->get('num') === 'date') {
            $this->sortable = false;
        }

        // Set default 404 Not Found status to error page
        if ($this->isErrorPage() && !$this->has('response_status')) {
            $this->set('response_status', 404);
        }
    }

    /**
     * Send page headers
     */
    protected function sendHeaders()
    {
        if ($this->has('response_status')) {
            Header::status($this->get('response_status'));
        }
        if (!empty($this->headers())) {
            foreach ($this->headers() as $name => $value) {
                Header::send($name, $value);
            }
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
