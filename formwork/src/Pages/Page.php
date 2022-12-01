<?php

namespace Formwork\Pages;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Files\Files;
use Formwork\Formwork;
use Formwork\Metadata\MetadataCollection;
use Formwork\Pages\Templates\Template;
use Formwork\Pages\Traits\PageData;
use Formwork\Pages\Traits\PageStatus;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Parsers\YAML;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use RuntimeException;

class Page implements Arrayable
{
    use PageData;
    use PageStatus;
    use PageTraversal;
    use PageUid;
    use PageUri;

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
    protected string $timestamp;

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
    protected ?string $language;

    /**
     * Available page languages
     */
    protected array $availableLanguages;

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
     * Page frontmatter
     */
    protected array $frontmatter;

    /**
     * Page fields
     */
    protected FieldCollection $fields;

    /**
     * Page canonical URI
     */
    protected ?string $canonical;

    /**
     * Page metadata
     */
    protected MetadataCollection $metadata;

    /**
     * Page response status
     */
    protected int $responseStatus;

    /**
     * Page num (used to order pages)
     */
    protected ?int $num;

    /**
     * Create a new Page instance
     */
    public function __construct(string $path)
    {
        $this->path = FileSystem::normalizePath($path . DS);

        $this->relativePath = Str::prepend(Path::makeRelative($this->path, Formwork::instance()->site()->path(), DS), DS);

        $this->route = Uri::normalize(preg_replace('~[/\\\\](\d+-)~', '/', $this->relativePath));

        $this->name = basename($this->relativePath);

        $this->slug = basename($this->route);

        $this->language = null;

        $this->availableLanguages = [];

        $this->loadFiles();

        if (!$this->isEmpty()) {
            $this->loadContents();
        }
    }

    public function __toString(): string
    {
        return $this->title() ?? $this->slug();
    }

    /**
     * Return page default data
     */
    public function defaults(): array
    {
        $defaults = [
            'published'      => true,
            'routable'       => true,
            'listed'         => true,
            'searchable'     => true,
            'cacheable'      => true,
            'orderable'      => true,
            'canonical'      => null,
            'headers'        => [],
            'responseStatus' => 200,
            'metadata'       => []
        ];

        // Merge with scheme default field values
        $defaults = array_merge($defaults, Arr::reject($this->fields()->pluck('default'), fn ($value) => $value === null));

        // If the page hasn't a num, by default it won't be listed
        if ($this->num() === null) {
            $defaults['listed'] = false;
        }

        // If the page hasn't a num or numbering is 'date', by default it won't be orderable
        if ($this->num() === null || $this->scheme->get('num') === 'date') {
            $defaults['orderable'] = false;
        }

        return $defaults;
    }

    /**
     * Get the canonical page URI, or `null` if not available
     */
    public function canonical(): ?string
    {
        if (isset($this->canonical)) {
            return $this->canonical;
        }

        return $this->canonical = !empty($this->data['canonical'])
            ? Path::normalize($this->data['canonical'])
            : null;
    }

    /**
     * Get page metadata
     */
    public function metadata(): MetadataCollection
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $metadata = Formwork::instance()->site()->metadata()->clone();
        $metadata->setMultiple($this->data['metadata']);
        return $this->metadata = $metadata;
    }

    /**
     * Get the page response status
     */
    public function responseStatus(): int
    {
        if (isset($this->responseStatus)) {
            return $this->responseStatus;
        }

        // Normalize response status
        $this->responseStatus = (int) $this->data['responseStatus'];

        // Get a default 404 Not Found status for the error page
        if ($this->isErrorPage() && $this->responseStatus() === 200 && !isset($this->frontmatter['responseStatus'])) {
            $this->responseStatus = 404;
        }

        return $this->responseStatus;
    }

    /**
     * Page last modified time
     */
    public function lastModifiedTime(): int
    {
        if (isset($this->lastModifiedTime)) {
            return $this->lastModifiedTime;
        }

        return $this->lastModifiedTime = FileSystem::lastModifiedTime($this->path . $this->filename);
    }

    /**
     * Timestamp representing the publication date (modification time as fallback)
     */
    public function timestamp(): int
    {
        if (isset($this->timestamp)) {
            return $this->timestamp;
        }

        return $this->timestamp = isset($this->data['publishDate'])
            ? Date::toTimestamp($this->data['publishDate'])
            : $this->lastModifiedTime();
    }

    /**
     * Get page num
     */
    public function num(): ?int
    {
        if (isset($this->num)) {
            return $this->num;
        }

        preg_match(self::NUM_REGEX, $this->name, $matches);
        return $this->num = isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Set page language
     */
    public function setLanguage(string $language): void
    {
        if (!$this->hasLanguage($language)) {
            throw new RuntimeException(sprintf('Invalid page language "%s"', $language));
        }
        $path = $this->path;
        $this->resetProperties();
        $this->language = $language;
        $this->__construct($path);
    }

    /**
     * Get page files
     */
    public function files(): Files
    {
        return $this->files;
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
     */
    public function render(): string
    {
        return $this->template()->render(true);
    }

    /**
     * Return whether page is empty
     */
    public function isEmpty(): bool
    {
        return !isset($this->filename);
    }

    public function isPublished(): bool
    {
        return $this->status() === self::PAGE_STATUS_PUBLISHED;
    }

    /**
     * Return whether this is the currently active page
     */
    public function isCurrent(): bool
    {
        return Formwork::instance()->site()->currentPage() === $this;
    }

    /**
     * Return whether the page is site
     */
    public function isSite(): bool
    {
        return false;
    }

    /**
     * Return whether the page is the index page
     */
    public function isIndexPage(): bool
    {
        return trim($this->route(), '/') === Formwork::instance()->config()->get('pages.index');
    }

    /**
     * Return whether the page is the error page
     */
    public function isErrorPage(): bool
    {
        return trim($this->route(), '/') === Formwork::instance()->config()->get('pages.error');
    }

    /**
     * Return whether the page is deletable
     */
    public function isDeletable(): bool
    {
        return !($this->hasChildren() || $this->isIndexPage() || $this->isErrorPage());
    }

    /**
     * Return whether the page has the specified language
     */
    public function hasLanguage(string $language): bool
    {
        return in_array($language, $this->availableLanguages, true);
    }

    /**
     * Reload page
     *
     * @internal
     */
    public function reload(): void
    {
        $path = $this->path;
        $this->resetProperties();
        $this->__construct($path);
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

                if (Formwork::instance()->site()->templates()->has($name)) {
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

            $currentLanguage = $this->language ?? Formwork::instance()->site()->languages()->current();

            $key = isset($contentFiles[$currentLanguage]) ? $currentLanguage : array_keys($contentFiles)[0];

            // Set actual language
            $this->language = $key ?: null;

            $this->filename = $contentFiles[$key]['filename'];

            $this->template = new Template($contentFiles[$key]['template'], $this);

            $this->scheme = Formwork::instance()->schemes()->get('pages', $this->template);

            $this->fields = $this->scheme()->fields();
        }

        $this->files = Files::fromPath($this->path, $files);
    }

    /**
     * Parse page content
     */
    protected function loadContents(): void
    {
        $contents = FileSystem::read($this->path . $this->filename);

        if (!preg_match('/(?:\s|^)-{3}\s*(.+?)\s*-{3}\s*(.*?)\s*$/s', $contents, $matches)) {
            throw new RuntimeException('Invalid page format');
        }

        [, $rawFrontmatter, $rawContent] = $matches;

        $this->frontmatter = YAML::parse($rawFrontmatter);

        $rawContent = str_replace("\r\n", "\n", $rawContent);

        $this->data = array_merge($this->defaults(), $this->frontmatter, ['content' => $rawContent]);

        $this->fields->validate($this->data);
    }

    /**
     * Reset page properties
     */
    protected function resetProperties(): void
    {
        foreach (array_keys(get_class_vars(static::class)) as $property) {
            unset($this->$property);
        }
    }
}
