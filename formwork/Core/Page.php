<?php

namespace Formwork\Core;

use Formwork\Files\Files;
use Formwork\Metadata\Metadata;
use Formwork\Parsers\Markdown;
use Formwork\Parsers\YAML;
use Formwork\Template\Template;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\Str;
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
    protected $availableLanguages = [];

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
     * Unprocessed page frontmatter
     *
     * @var string
     */
    protected $rawFrontmatter;

    /**
     * Page frontmatter
     *
     * @var array
     */
    protected $frontmatter = [];

    /**
     * Unprocessed page content
     *
     * @var string
     */
    protected $rawContent;

    /**
     * Unprocessed page summary
     *
     * @var string
     */
    protected $rawSummary;

    /**
     * Page summary
     *
     * @var string
     */
    protected $summary;

    /**
     * Unprocessed page body text
     *
     * @var string
     */
    protected $rawBody;

    /**
     * Page body text
     *
     * @var string
     */
    protected $body;

    /**
     * Page status
     *
     * @var string
     */
    protected $status;

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
     */
    public function __construct(string $path)
    {
        $this->path = FileSystem::normalize($path);
        $this->relativePath = Uri::normalize(Str::removeStart($this->path, Formwork::instance()->option('content.path')));
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
     */
    public function defaults(): array
    {
        $defaults = [
            'published'  => true,
            'routable'   => true,
            'visible'    => true,
            'searchable' => true,
            'cacheable'  => true,
            'sortable'   => true,
            'headers'    => [],
            'metadata'   => []
        ];

        // Merge with scheme default field values
        $defaults = array_merge($defaults, $this->template->scheme()->defaultFieldValues());

        // If the page hasn't a num, by default it won't be visible
        if ($this->num() === null) {
            $defaults['visible'] = false;
        }

        return $defaults;
    }

    /**
     * Reload page
     */
    public function reload(): void
    {
        $vars = ['filename', 'template', 'status', 'absoluteUri', 'lastModifiedTime', 'parent', 'parents', 'level', 'children', 'descendants', 'siblings'];
        foreach ($vars as $var) {
            $this->$var = null;
        }
        $this->__construct($this->path);
    }

    /**
     * Return whether page is empty
     */
    public function isEmpty(): bool
    {
        return $this->filename === null;
    }

    /**
     * @inheritdoc
     */
    public function lastModifiedTime(): int
    {
        return max(FileSystem::lastModifiedTime($this->path . $this->filename), parent::lastModifiedTime());
    }

    /**
     * Get page num
     */
    public function num(): ?int
    {
        preg_match(self::NUM_REGEX, $this->id, $matches);
        return isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Return whether this is the currently active page
     */
    public function isCurrent(): bool
    {
        return Formwork::instance()->site()->currentPage() === $this;
    }

    /**
     * @inheritdoc
     */
    public function date(string $format = null): string
    {
        if ($format === null) {
            $format = Formwork::instance()->option('date.format');
        }
        if ($this->has('publish-date')) {
            return date($format, strtotime($this->data['publish-date']));
        }
        return parent::date($format);
    }

    /**
     * Get page status
     */
    public function status(): string
    {
        if ($this->status !== null) {
            return $this->status;
        }
        if ($this->published()) {
            $status = self::PAGE_STATUS_PUBLISHED;
        }
        if (!$this->routable()) {
            $status = self::PAGE_STATUS_NOT_ROUTABLE;
        }
        if (!$this->published()) {
            $status = self::PAGE_STATUS_NOT_PUBLISHED;
        }
        return $this->status = $status;
    }

    /**
     * Return a PageCollection containing page siblings
     */
    public function siblings(): PageCollection
    {
        if ($this->siblings !== null) {
            return $this->siblings;
        }
        $parentPath = dirname($this->path) . DS;
        return $this->siblings = PageCollection::fromPath($parentPath)->remove($this);
    }

    /**
     * Return whether page has siblings
     */
    public function hasSiblings(): bool
    {
        return !$this->siblings()->isEmpty();
    }

    /**
     * @inheritdoc
     */
    public function isSite(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isIndexPage(): bool
    {
        return trim($this->route(), '/') === Formwork::instance()->option('pages.index');
    }

    /**
     * @inheritdoc
     */
    public function isErrorPage(): bool
    {
        return trim($this->route(), '/') === Formwork::instance()->option('pages.error');
    }

    /**
     * @inheritdoc
     */
    public function isDeletable(): bool
    {
        if ($this->hasChildren() || $this->isSite() || $this->isIndexPage() || $this->isErrorPage()) {
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function metadata(): Metadata
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }
        $metadata = clone Formwork::instance()->site()->metadata();
        $metadata->setMultiple($this->data['metadata']);
        return $this->metadata = $metadata;
    }

    /**
     * Return whether page has a language
     */
    public function hasLanguage(string $language): bool
    {
        return in_array($language, $this->availableLanguages, true);
    }

    /**
     * Set page language
     */
    public function setLanguage(string $language): void
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
     *
     * @deprecated
     */
    public function file(string $file): ?string
    {
        trigger_error(static::class . '::file() is deprecated since Formwork 1.4.0, access files from ' . static::class . '::files() instead', E_USER_DEPRECATED);
        return $this->files()->has($file) ? Str::removeStart($this->files()->get($file)->path(), ROOT_PATH) : null;
    }

    /**
     * Return a Files collection containing only images
     */
    public function images(): Files
    {
        return $this->files()->filterByType('image');
    }

    /**
     * Render page to string
     *
     * @param array $vars Variables to pass to the page
     */
    public function renderToString(array $vars = []): string
    {
        return $this->template()->render($vars, true);
    }

    /**
     * Render page and return rendered content
     *
     * @param array $vars        Variables to pass to the page
     * @param bool  $sendHeaders Whether to send headers before rendering
     */
    public function render(array $vars = [], bool $sendHeaders = true): string
    {
        if ($sendHeaders) {
            $this->sendHeaders();
        }
        echo $renderedPage = $this->renderToString($vars);
        return $renderedPage;
    }

    /**
     * Return an array containing page data
     */
    public function toArray(): array
    {
        return [
            'route'    => $this->route(),
            'uri'      => $this->uri(),
            'id'       => $this->id(),
            'slug'     => $this->slug(),
            'template' => $this->template()->name(),
            'num'      => $this->num(),
            'data'     => $this->data()
        ];
    }

    /**
     * Return raw content
     */
    public function rawContent(): string
    {
        if ($this->rawContent !== null) {
            return $this->rawContent;
        }
        return $this->rawContent = str_replace("\r\n", "\n", empty($this->rawSummary) ? $this->rawBody : $this->rawSummary . "\n\n===\n\n" . $this->rawBody);
    }

    /**
     * Return summary text
     */
    public function summary(): string
    {
        if ($this->summary !== null) {
            return $this->summary;
        }
        return $this->summary = Markdown::parse($this->rawSummary, ['baseRoute' => $this->route]);
    }

    /**
     * Return body text
     */
    public function body(): string
    {
        if ($this->body !== null) {
            return $this->body;
        }
        return $this->body = Markdown::parse($this->rawBody, ['baseRoute' => $this->route]);
    }

    /**
     * Return page content (summary and body text)
     */
    public function content(): string
    {
        return $this->summary() . $this->body();
    }

    /**
     * Load files related to page
     */
    protected function loadFiles(): void
    {
        $contentFiles = [];
        $files = [];

        foreach (FileSystem::listFiles($this->path) as $file) {
            $name = FileSystem::name($file);
            $extension = '.' . FileSystem::extension($file);
            if ($extension === Formwork::instance()->option('content.extension')) {
                $language = null;
                if (preg_match('/([a-z0-9]+)\.([a-z]+)/', $name, $matches)) {
                    // Parse double extension
                    [$match, $name, $language] = $matches;
                }
                if (Formwork::instance()->site()->hasTemplate($name)) {
                    $contentFiles[$language] = [
                        'filename' => $file,
                        'template' => $name
                    ];
                    if ($language !== null && !in_array($language, $this->availableLanguages, true)) {
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
     * Parse page content
     */
    protected function parse(): void
    {
        $contents = FileSystem::read($this->path . $this->filename);
        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(?:(.+?)\s+={3}\s+)?(.*?)\s*$/s', $contents, $matches)) {
            throw new RuntimeException('Invalid page format');
        }
        [$match, $this->rawFrontmatter, $this->rawSummary, $this->rawBody] = $matches;
        $this->frontmatter = YAML::parse($this->rawFrontmatter);
        $this->data = array_merge($this->defaults(), $this->frontmatter);
    }

    /**
     * Process page data
     */
    protected function processData(): void
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

        if ($this->num() === null || $this->template()->scheme()->get('num') === 'date') {
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
    protected function sendHeaders(): void
    {
        if ($this->has('response_status')) {
            Header::status((int) $this->get('response_status'));
        }
        if (!empty($this->headers())) {
            foreach ($this->headers() as $name => $value) {
                Header::send($name, $value);
            }
        }
    }

    public function __toString(): string
    {
        return $this->id();
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
