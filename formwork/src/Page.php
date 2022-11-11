<?php

namespace Formwork;

use Formwork\Files\Files;
use Formwork\Metadata\Metadata;
use Formwork\Parsers\Markdown;
use Formwork\Parsers\YAML;
use Formwork\Schemes\Scheme;
use Formwork\Template\Template;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;

class Page extends AbstractPage
{
    /**
     * Page num regex
     */
    public const NUM_REGEX = '/^(\d+)-/';

    /**
     * Page 'published' status
     */
    public const PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page 'not published' status
     */
    public const PAGE_STATUS_NOT_PUBLISHED = 'not-published';

    /**
     * Page 'not routable' status
     */
    public const PAGE_STATUS_NOT_ROUTABLE = 'not-routable';

    /**
     * Page id
     *
     * @deprecated Use the name property
     */
    protected string $id;

    /**
     * Page name (the name of the containing directory)
     */
    protected string $name;

    /**
     * Page slug
     */
    protected string $slug;

    /**
     * Page language
     */
    protected ?string $language = null;

    /**
     * Available page languages
     */
    protected array $availableLanguages = [];

    /**
     * Page filename
     */
    protected string $filename;

    /**
     * Page template
     */
    protected Template $template;

    /**
     * Page scheme
     */
    protected Scheme $scheme;

    /**
     * Page files
     */
    protected Files $files;

    /**
     * Unprocessed page frontmatter
     */
    protected string $rawFrontmatter;

    /**
     * Page frontmatter
     */
    protected array $frontmatter = [];

    /**
     * Unprocessed page content
     */
    protected string $rawContent;

    /**
     * Page content
     */
    protected string $content;

    /**
     * Unprocessed page summary
     */
    protected string $rawSummary;

    /**
     * Page summary
     */
    protected string $summary;

    /**
     * Unprocessed page body text
     */
    protected string $rawBody;

    /**
     * Page body text
     */
    protected string $body;

    /**
     * Page status
     */
    protected string $status;

    /**
     * Whether page is published
     */
    protected bool $published;

    /**
     * Whether page is routable
     */
    protected bool $routable;

    /**
     * Whether page is visible
     */
    protected bool $visible;

    /**
     * Whether page is sortable
     */
    protected bool $sortable;

    /**
     * PageCollection containing page siblings
     */
    protected PageCollection $siblings;

    /**
     * Create a new Page instance
     */
    public function __construct(string $path)
    {
        $this->path = FileSystem::normalizePath($path . DS);
        $this->relativePath = Str::wrap(Str::removeStart($this->path, Formwork::instance()->site()->path()), DS);
        $this->route = Uri::normalize(preg_replace('~[/\\\\](\d+-)~', '/', $this->relativePath));
        $this->name = basename($this->relativePath);
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
        $defaults = array_merge($defaults, $this->scheme->defaultFieldValues());

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
        $vars = ['filename', 'template', 'rawContent', 'rawSummary', 'summary', 'rawBody', 'body', 'status', 'absoluteUri', 'lastModifiedTime', 'parent', 'parents', 'level', 'children', 'descendants', 'siblings'];
        foreach ($vars as $var) {
            unset($this->$var);
        }
        $this->__construct($this->path);
    }

    /**
     * Return whether page is empty
     */
    public function isEmpty(): bool
    {
        return !isset($this->filename);
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
        preg_match(self::NUM_REGEX, $this->name, $matches);
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
        $format ??= Formwork::instance()->config()->get('date.format');
        if ($this->has('publish-date')) {
            return date($format, Date::toTimestamp($this->data['publish-date']));
        }
        return parent::date($format);
    }

    /**
     * Get page status
     */
    public function status(): string
    {
        if (isset($this->status)) {
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
        if (isset($this->siblings)) {
            return $this->siblings;
        }
        $parentPath = dirname($this->path) . DS;
        return $this->siblings = PageCollection::fromPath($parentPath)->without($this);
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
        return trim($this->route(), '/') === Formwork::instance()->config()->get('pages.index');
    }

    /**
     * @inheritdoc
     */
    public function isErrorPage(): bool
    {
        return trim($this->route(), '/') === Formwork::instance()->config()->get('pages.error');
    }

    /**
     * @inheritdoc
     */
    public function isDeletable(): bool
    {
        return !($this->hasChildren() || $this->isSite() || $this->isIndexPage() || $this->isErrorPage());
    }

    /**
     * @inheritdoc
     */
    public function metadata(): Metadata
    {
        if (isset($this->metadata)) {
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
            throw new RuntimeException(sprintf('Invalid page language "%s"', $language));
        }
        $this->language = $language;
        $this->__construct($this->path);
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
        return $this->template()->render(true);
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
            'name'     => $this->name(),
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
        if (isset($this->rawContent)) {
            return $this->rawContent;
        }
        return $this->rawContent = str_replace("\r\n", "\n", empty($this->rawSummary) ? $this->rawBody : $this->rawSummary . "\n\n===\n\n" . $this->rawBody);
    }

    /**
     * Return summary text
     */
    public function summary(): string
    {
        if (isset($this->summary)) {
            return $this->summary;
        }
        return $this->summary = Markdown::parse($this->rawSummary, ['baseRoute' => $this->route]);
    }

    /**
     * Return body text
     */
    public function body(): string
    {
        if (isset($this->body)) {
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
            if ($extension === Formwork::instance()->config()->get('content.extension')) {
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
            } elseif (in_array($extension, Formwork::instance()->config()->get('files.allowed_extensions'), true)) {
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
            $this->scheme = Formwork::instance()->schemes()->get('pages', $this->template);
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
            $this->published = $this->published && Date::toTimestamp($this->get('publish-date')) < time();
        }

        if ($this->has('unpublish-date')) {
            $this->published = $this->published && Date::toTimestamp($this->get('unpublish-date')) > time();
        }

        $this->routable = $this->data['routable'];

        $this->visible = $this->data['visible'];

        // If the page isn't published, it won't also be visible
        if (!$this->published) {
            $this->visible = false;
        }

        $this->sortable = $this->data['sortable'];

        if ($this->num() === null || $this->scheme->get('num') === 'date') {
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
        foreach ($this->headers() as $name => $value) {
            Header::send($name, $value);
        }
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
