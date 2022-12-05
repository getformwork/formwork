<?php

namespace Formwork\Pages;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Files\FileCollection;
use Formwork\Formwork;
use Formwork\Languages\Language;
use Formwork\Languages\Languages;
use Formwork\Metadata\MetadataCollection;
use Formwork\Pages\Templates\Template;
use Formwork\Pages\Traits\PageData;
use Formwork\Pages\Traits\PageStatus;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Schemes\Scheme;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use ReflectionClass;
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
     * Page `published` status
     */
    public const PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page `not published` status
     */
    public const PAGE_STATUS_NOT_PUBLISHED = 'not-published';

    /**
     * Page `not routable` status
     */
    public const PAGE_STATUS_NOT_ROUTABLE = 'not-routable';

    /**
     * Page path
     */
    protected ?string $path = null;

    /**
     * Page path relative to the content path
     */
    protected ?string $relativePath = null;

    /**
     * Page content file
     */
    protected ?ContentFile $contentFile = null;

    /**
     * Page route
     */
    protected ?string $route = null;

    /**
     * Page canonical route
     */
    protected ?string $canonicalRoute = null;

    /**
     * Page slug
     */
    protected ?string $slug = null;

    /**
     * Page num used to order pages
     */
    protected ?int $num = null;

    /**
     * Available page languages
     */
    protected Languages $languages;

    /**
     * Current page language
     */
    protected ?Language $language = null;

    /**
     * Page scheme
     */
    protected Scheme $scheme;

    /**
     * Page fields
     */
    protected FieldCollection $fields;

    /**
     * Page template
     */
    protected Template $template;

    /**
     * Page metadata
     */
    protected MetadataCollection $metadata;

    /**
     * Page files
     */
    protected FileCollection $files;

    /**
     * Page HTTP response status
     */
    protected int $responseStatus;

    /**
     * Page loading state
     */
    protected bool $loaded = false;

    public function __construct(array $data = [])
    {
        $this->setMultiple($data);

        $this->loadFiles();

        if ($this->hasContentFile() && !$this->contentFile->isEmpty()) {
            $this->data = array_merge(
                $this->data,
                $this->contentFile->frontmatter(),
                ['content' => $this->contentFile->content()]
            );
        }

        $this->fields->validate($this->data);

        $this->loaded = true;
    }

    public function __toString(): string
    {
        return $this->title() ?? $this->slug();
    }

    /**
     * Create page from the given path
     */
    public static function fromPath(string $path, array $data = []): static
    {
        return new static(['path' => $path] + $data);
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
            'canonicalRoute' => null,
            'headers'        => [],
            'responseStatus' => 200,
            'metadata'       => [],
            'content'        => ''
        ];

        // Merge with scheme default field values
        $defaults = array_merge($defaults, Arr::reject($this->fields()->pluck('default'), fn ($value) => $value === null));

        // If the page doesn't have a route, by default it won't be routable nor cacheable
        if ($this->route() === null) {
            $defaults['routable'] = false;
            $defaults['cacheable'] = false;
        }

        // If the page doesn't have a num, by default it won't be listed
        if ($this->num() === null) {
            $defaults['listed'] = false;
        }

        // If the page doesn't have a num or numbering is `date`, by default it won't be orderable
        if ($this->num() === null || $this->scheme->get('num') === 'date') {
            $defaults['orderable'] = false;
        }

        return $defaults;
    }

    /**
     * Get page path
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Get page relative path
     */
    public function relativePath(): ?string
    {
        return $this->relativePath;
    }

    /**
     * Get page filename
     */
    public function contentFile(): ?ContentFile
    {
        return $this->contentFile;
    }

    /**
     * Get page route
     */
    public function route(): ?string
    {
        return $this->route;
    }

    /**
     * Get the canonical page URI, or `null` if not available
     */
    public function canonicalRoute(): ?string
    {
        if (isset($this->canonicalRoute)) {
            return $this->canonicalRoute;
        }

        return $this->canonicalRoute = !empty($this->data['canonicalRoute'])
            ? Path::normalize($this->data['canonicalRoute'])
            : null;
    }

    /**
     * Get page slug
     */
    public function slug(): ?string
    {
        return $this->slug;
    }

    /**
     * Get page num
     */
    public function num(): ?int
    {
        if (isset($this->num)) {
            return $this->num;
        }

        preg_match(self::NUM_REGEX, basename($this->relativePath()), $matches);
        return $this->num = isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Get page languages
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Get page language
     */
    public function language(): ?Language
    {
        return $this->language;
    }

    /**
     * Get page scheme
     */
    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    /**
     * Get page fields
     */
    public function fields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Get page template
     */
    public function template(): Template
    {
        return $this->template;
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
     * Get page files
     */
    public function files(): FileCollection
    {
        return $this->files;
    }

    /**
     * Get page HTTP response status
     */
    public function responseStatus(): int
    {
        if (isset($this->responseStatus)) {
            return $this->responseStatus;
        }

        // Normalize response status
        $this->responseStatus = (int) $this->data['responseStatus'];

        // Get a default 404 Not Found status for the error page
        if ($this->isErrorPage() && $this->responseStatus() === 200
            && !isset($this->contentFile, $this->contentFile->frontmatter()['responseStatus'])) {
            $this->responseStatus = 404;
        }

        return $this->responseStatus;
    }

    /**
     * Set page language
     */
    public function setLanguage(Language|string $language): void
    {
        if (is_string($language)) {
            $language = new Language($language);
        }

        if (!$this->hasLoaded()) {
            $this->language = $language;
            return;
        }

        if ($this->languages()->current()->code() !== ($code = $language->code())) {
            if (!$this->languages()->available()->has($code)) {
                throw new InvalidArgumentException(sprintf('Invalid page language "%s"', $code));
            }
            $this->reload(['language' => $language]);
        }
    }

    /**
     * Return a Files collection containing only images
     */
    public function images(): FileCollection
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
     * Return whether the page has a content file
     */
    public function hasContentFile(): bool
    {
        return $this->contentFile !== null;
    }

    /**
     * Return whether the page content data is empty
     */
    public function isEmpty(): bool
    {
        return $this->contentFile?->frontmatter() !== [];
    }

    /**
     * Return whether the page is published
     */
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
     * Return whether the page has loaded
     */
    public function hasLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Reload page
     *
     * @internal
     */
    public function reload(array $data = []): void
    {
        if (!$this->hasLoaded()) {
            throw new RuntimeException('Unable to reload, the page has not been loaded yet');
        }
        $path = $this->path;
        $this->resetProperties();
        $this->__construct($data + ['path' => $path]);
    }

    /**
     * Set page path
     */
    protected function setPath(string $path): void
    {
        $this->path = FileSystem::normalizePath($path . DS);

        $this->relativePath = Str::prepend(Path::makeRelative($this->path, Formwork::instance()->site()->path(), DS), DS);

        $this->route ??= Uri::normalize(preg_replace('~[/\\\\](\d+-)~', '/', $this->relativePath));

        $this->slug ??= basename($this->route);
    }

    /**
     * Load files related to page
     */
    protected function loadFiles(): void
    {
        /**
         * @var array<string, string>
         */
        $contentFiles = [];

        /**
         * @var array<string>
         */
        $files = [];

        /**
         * @var array<string>
         */
        $languages = [];

        $config = Formwork::instance()->config();

        $site = Formwork::instance()->site();

        if (isset($this->path) && FileSystem::isDirectory($this->path, assertExists: false)) {
            foreach (FileSystem::listFiles($this->path) as $file) {
                $name = FileSystem::name($file);

                $extension = '.' . FileSystem::extension($file);

                if ($extension === $config->get('content.extension')) {
                    $language = null;

                    if (preg_match('/([a-z0-9]+)\.([a-z]+)/', $name, $matches)) {
                        // Parse double extension
                        [, $name, $language] = $matches;
                    }

                    if ($site->templates()->has($name)) {
                        $contentFiles[$language] = [
                            'path'     => FileSystem::joinPaths($this->path, $file),
                            'filename' => $file,
                            'template' => $name
                        ];
                        if ($language !== null && !in_array($language, $languages, true)) {
                            $languages[] = $language;
                        }
                    }
                } elseif (in_array($extension, $config->get('files.allowed_extensions'), true)) {
                    $files[] = $file;
                }
            }
        }

        if (!empty($contentFiles)) {
            // Get correct content file based on current language
            ksort($contentFiles);

            // Language may already be set
            $currentLanguage = $this->language ?? $site->languages()->current();

            /**
             * @var string
             */
            $key = isset($currentLanguage, $contentFiles[$currentLanguage->code()])
                ? $currentLanguage->code()
                : array_keys($contentFiles)[0];

            // Set actual language
            $this->language ??= $key ? new Language($key) : null;

            $this->contentFile ??= new ContentFile($contentFiles[$key]['path']);

            $this->template ??= new Template($contentFiles[$key]['template'], $this);

            $this->scheme ??= Formwork::instance()->schemes()->get('pages', $this->template);
        } else {
            $this->template ??= new Template('default', $this);

            $this->scheme ??= Formwork::instance()->schemes()->get('pages', 'default');
        }

        $this->fields ??= $this->scheme()->fields();

        $defaultLanguage = in_array((string) $site->languages()->default(), $languages, true)
            ? $site->languages()->default()
            : null;

        $this->languages ??= new Languages([
            'available' => $languages,
            'default'   => $defaultLanguage,
            'current'   => $this->language ?? null,
            'requested' => $site->languages()->requested(),
            'preferred' => $site->languages()->preferred()
        ]);

        $this->files ??= isset($this->path) ? FileCollection::fromPath($this->path, $files) : new FileCollection();

        $this->data = array_merge($this->defaults(), $this->data);
    }

    /**
     * Reset page properties
     */
    protected function resetProperties(): void
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getProperties() as $property) {
            unset($this->{$property->getName()});

            if ($property->hasDefaultValue()) {
                $this->{$property->getName()} = $property->getDefaultValue();
            }
        }
    }
}