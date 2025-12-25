<?php

namespace Formwork\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Http\ResponseStatus;
use Formwork\Languages\Language;
use Formwork\Languages\Languages;
use Formwork\Metadata\MetadataCollection;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Model\Model;
use Formwork\Pages\Events\PageAfterDeleteEvent;
use Formwork\Pages\Events\PageAfterDuplicateEvent;
use Formwork\Pages\Events\PageAfterSaveEvent;
use Formwork\Pages\Events\PageBeforeDeleteEvent;
use Formwork\Pages\Events\PageBeforeDuplicateEvent;
use Formwork\Pages\Events\PageBeforeSaveEvent;
use Formwork\Pages\Events\PageLoadedEvent;
use Formwork\Pages\Events\PageRenderEvent;
use Formwork\Pages\Traits\PageStatus;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Parsers\Yaml;
use Formwork\Templates\Template;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use ReflectionClass;
use RuntimeException;
use Stringable;
use UnexpectedValueException;

class Page extends Model implements Stringable
{
    use PageStatus;
    use PageTraversal;
    use PageUid;
    use PageUri;

    /**
     * Page `published` status
     */
    public const string PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page `not published` status
     */
    public const string PAGE_STATUS_NOT_PUBLISHED = 'notPublished';

    /**
     * Page num regex
     */
    public const string NUM_REGEX = '/^(\d+)-/';

    protected const string MODEL_IDENTIFIER = 'page';

    /**
     * Ignored field names on frontmatter generation
     *
     * @var list<string>
     */
    protected const array IGNORED_FIELD_NAMES = ['content', 'slug', 'template', 'parent'];

    /**
     * Ignored field types on frontmatter generation
     *
     * @var list<string>
     */
    protected const array IGNORED_FIELD_TYPES = ['upload'];

    /**
     * Slug regex
     */
    protected const string SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i';

    /**
     * Datetime format used for page numbering with `date` mode
     */
    protected const string DATE_NUM_FORMAT = 'Ymd';

    /**
     * Page path
     */
    protected ?string $path = null;

    /**
     * Page path relative to the content path
     */
    #[ReadonlyModelProperty]
    protected ?string $relativePath = null;

    /**
     * Page route
     */
    #[ReadonlyModelProperty]
    protected ?string $route = null;

    /**
     * Page slug
     */
    protected ?string $slug = null;

    /**
     * Page num used to order pages
     */
    protected ?int $num = null;

    /**
     * Page content file
     */
    #[ReadonlyModelProperty]
    protected ?ContentFile $contentFile = null;

    /**
     * Page last modified time
     */
    #[ReadonlyModelProperty]
    protected int $lastModifiedTime;

    /**
     * Page template
     */
    protected Template $template;

    /**
     * Available page languages
     */
    #[ReadonlyModelProperty]
    protected Languages $languages;

    /**
     * Current page language
     */
    protected ?Language $language = null;

    /**
     * Page metadata
     */
    protected MetadataCollection $metadata;

    /**
     * Page files
     */
    #[ReadonlyModelProperty]
    protected FileCollection $files;

    /**
     * Page HTTP response status
     */
    protected ResponseStatus $responseStatus;

    /**
     * Page loading state
     */
    #[ReadonlyModelProperty]
    protected bool $loaded = false;

    /**
     * Reference to the site
     */
    protected Site $site;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        ?App $app = null,
    ) {
        if ($app !== null) {
            $this->app = $app;
        }

        $this->setMultiple($data);

        $this->load();
    }

    public function __toString(): string
    {
        return (string) ($this->title() ?? $this->slug());
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this, $name) && Str::startsWith($name, 'set')) {
            trigger_error(sprintf('Calling page setter methods directly is deprecated since Formwork 2.3.0. Use $page->set(\'%s\', $value) instead of $page->%s($value)', strtolower(Str::after($name, 'set')), $name), E_USER_DEPRECATED);
            return $this->{$name}(...$arguments);
        }
        return parent::__call($name, $arguments);
    }

    /**
     * Return site
     */
    public function site(): Site
    {
        return $this->site ??= $this->app()->site();
    }

    /**
     * Return page default data
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [
            'published'      => true,
            'publishDate'    => null,
            'unpublishDate'  => null,
            'routable'       => true,
            'listed'         => true,
            'searchable'     => true,
            'cacheable'      => true,
            'orderable'      => true,
            'allowChildren'  => true,
            'canonicalRoute' => null,
            'headers'        => [],
            'responseStatus' => 200,
            'metadata'       => [],
            'taxonomy'       => [],
            'content'        => '',
        ];

        // Merge with scheme default field values
        $defaults = Arr::override($defaults, Arr::undot($this->fields()->extract('default')));

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
        if ($this->num() === null || $this->scheme->options()->get('num') === 'date') {
            $defaults['orderable'] = false;
        }

        // If the page scheme disables children, by default it won't allow children
        if ($this->scheme()->options()->get('children') === false) {
            $defaults['allowChildren'] = false;
        }

        return $defaults;
    }

    /**
     * Save page contents and move files if needed
     *
     * @param string|null $language Language code to save the page in
     *
     * @throws UnexpectedValueException If parent or parent content path is missing
     * @throws InvalidValueException    If the language is invalid
     */
    public function save(?string $language = null): void
    {
        $this->app()->events()->dispatch(new PageBeforeSaveEvent($this));

        $this->write($language, copy: false);

        $this->app()->events()->dispatch(new PageAfterSaveEvent($this));
    }

    /**
     * Duplicate the page
     *
     * @param array<string, mixed> $with     Data to override in the duplicated page
     * @param string|null          $language Language code to duplicate the page in
     *
     * @throws UnexpectedValueException If parent or parent content path is missing
     * @throws InvalidValueException    If the language is invalid
     *
     * @since 2.2.0
     */
    public function duplicate(array $with = [], ?string $language = null): Page
    {
        if (!$this->isDuplicable()) {
            throw new RuntimeException('Cannot duplicate a non-duplicable page');
        }

        $this->app()->events()->dispatch(new PageBeforeDuplicateEvent($this, $with));

        $duplicatePage = clone $this;

        // Generate a unique slug by checking for existing copies
        $baseSlug = $this->slug();
        $newSlug = "{$baseSlug}-copy";
        $counter = 1;

        if ($this->parent() !== null) {
            $slugs = $this->parent()->children()->everyItem()->slug();
            while ($slugs->contains($newSlug)) {
                $counter++;
                $newSlug = "{$baseSlug}-copy-{$counter}";
            }
        }

        $duplicatePage->setMultiple([
            'path'           => null,
            'canonicalRoute' => null,
            'slug'           => $newSlug,
            ...$with,
        ]);

        $duplicatePage->write($language, copy: true);

        $this->app()->events()->dispatch(new PageAfterDuplicateEvent($this, $duplicatePage));

        return $duplicatePage;
    }

    /**
     * Delete the page
     *
     * @since 2.2.1
     *
     * @param bool $allLanguages Whether to delete all language versions of the page
     *
     * @throws RuntimeException If the page is not deletable
     */
    public function delete(bool $allLanguages = false): void
    {
        if (!$this->isDeletable()) {
            throw new RuntimeException('Cannot delete a non-deletable page');
        }

        $this->app()->events()->dispatch(new PageBeforeDeleteEvent($this));

        if ($this->contentPath() !== null) {
            // Delete just the content file only if there are more than one language
            if ($this->contentFile() !== null && !$allLanguages && count($this->languages()->available()) > 1) {
                FileSystem::delete($this->contentFile()->path());
            } else {
                FileSystem::delete($this->contentPath(), recursive: true);
            }
        }

        $this->app()->events()->dispatch(new PageAfterDeleteEvent($this));
    }

    /**
     * Render page to string
     */
    public function render(): string
    {
        $vars = [];

        $this->app()->events()->dispatch(new PageRenderEvent($this, $vars));

        return $this->template()->render(['page' => $this, ...$vars]);
    }

    /**
     * Reload the page from disk
     *
     * This method completely resets all page properties and reconstructs
     * the page by re-reading from disk. This ensures the page state matches
     * the file system after external changes (e.g., file uploads).
     *
     * After calling reload(), the page's internal fields ($this->fields) are
     * recreated and populated with fresh data from disk.
     *
     * @param array<string, mixed> $data Additional data to merge during reconstruction
     *
     * @throws RuntimeException If the page has not been loaded yet
     *
     * @internal
     */
    public function reload(array $data = []): void
    {
        if (!$this->hasLoaded()) {
            throw new RuntimeException('Unable to reload, the page has not been loaded yet');
        }

        $app = $this->app ?? null;

        $path = $this->path;
        $site = $this->site ?? null;

        $data = [...compact('site', 'path'), ...$data];

        $this->resetProperties();

        $this->__construct($data, $app);
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
     * Return page content path
     */
    public function contentPath(): ?string
    {
        return $this->path;
    }

    /**
     * Return page content relative path
     */
    public function contentRelativePath(): ?string
    {
        return $this->relativePath;
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
        return empty($this->data['canonicalRoute'])
            ? null
            : Path::normalize($this->data['canonicalRoute']);
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
        if ($this->num !== null) {
            return $this->num;
        }

        preg_match(self::NUM_REGEX, basename($this->relativePath() ?? ''), $matches);
        return $this->num = isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Return page icon
     */
    public function icon(): string
    {
        return $this->data['icon'] ?? $this->scheme()->options()->get('icon', 'page');
    }

    /**
     * Get page filename
     */
    public function contentFile(): ?ContentFile
    {
        return $this->contentFile;
    }

    /**
     * Get page template
     */
    public function template(): Template
    {
        return $this->template;
    }

    /**
     * Get page last modified time
     */
    public function lastModifiedTime(): ?int
    {
        if ($this->path === null) {
            return null;
        }

        $lastModifiedTime = $this->contentFile() !== null
            ? $this->contentFile()->lastModifiedTime()
            : FileSystem::lastModifiedTime($this->path);

        return $this->lastModifiedTime ??= $lastModifiedTime;
    }

    /**
     * Get page language
     */
    public function language(): ?Language
    {
        return $this->language;
    }

    /**
     * Get page languages
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Get page metadata
     */
    public function metadata(): MetadataCollection
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $metadata = $this->site()->metadata()->clone();
        $metadata->setMultiple($this->data['metadata']);
        return $this->metadata = $metadata;
    }

    /**
     * Get page taxonomy
     *
     * @return array<string, list<string>>
     *
     * @since 2.2.0
     */
    public function taxonomy(): array
    {
        return $this->data['taxonomy'];
    }

    /**
     * Get page HTTP response status
     */
    public function responseStatus(): ResponseStatus
    {
        if (isset($this->responseStatus)) {
            return $this->responseStatus;
        }

        // Normalize response status
        $this->responseStatus = ResponseStatus::fromCode((int) $this->data['responseStatus']);

        // Get a default 404 Not Found status for the error page
        if (
            $this->isErrorPage() && $this->responseStatus() === ResponseStatus::OK
            && $this->contentFile === null
        ) {
            $this->responseStatus = ResponseStatus::NotFound;
        }

        return $this->responseStatus;
    }

    /**
     * Get page files
     */
    public function files(): FileCollection
    {
        return $this->files;
    }

    /**
     * Return all page images
     */
    public function images(): FileCollection
    {
        return $this->files()->filterBy('type', 'image');
    }

    /**
     * Return all page videos
     */
    public function videos(): FileCollection
    {
        return $this->files()->filterBy('type', 'video');
    }

    /**
     * Return all page media files (images and videos)
     */
    public function media(): FileCollection
    {
        return $this->files()->filterBy('type', fn(string $type) => in_array($type, ['image', 'video'], true));
    }

    /**
     * Return whether the page has loaded
     */
    public function hasLoaded(): bool
    {
        return $this->loaded;
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
        return $this === $this->site()->indexPage();
    }

    /**
     * Return whether the page is the error page
     */
    public function isErrorPage(): bool
    {
        return $this === $this->site()->errorPage();
    }

    /**
     * Return whether the page is index or error page
     */
    public function isIndexOrErrorPage(): bool
    {
        return $this->isIndexPage() || $this->isErrorPage();
    }

    /**
     * Return whether this is the currently active page
     */
    public function isCurrent(): bool
    {
        return $this->site()->currentPage() === $this;
    }

    /**
     * Return whether the page is deletable
     */
    public function isDeletable(): bool
    {
        return !$this->hasChildren() && !$this->isIndexOrErrorPage();
    }

    /**
     * Return whether the page is duplicable
     *
     * @since 2.2.0
     */
    public function isDuplicable(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * Return whether the slug is editable
     *
     * @deprecated since 2.3.0 Use `!$this->isIndexOrErrorPage()` instead
     */
    public function isSlugEditable(): bool
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.3.0. Use !$page->isIndexOrErrorPage() instead.', __METHOD__), E_USER_DEPRECATED);
        return !$this->isIndexOrErrorPage();
    }

    /**
     * Return whether the slug is readonly
     *
     * @deprecated since 2.3.0 Use `$this->isIndexOrErrorPage()` instead
     */
    public function isSlugReadonly(): bool
    {
        trigger_error(sprintf('%s() is deprecated since Formwork 2.3.0. Use $page->isIndexOrErrorPage() instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->isIndexOrErrorPage();
    }

    /**
     * Load page from disk and initialize fields
     *
     * Data loading order (later sources override earlier ones):
     * 1. Page defaults (includes field defaults)
     * 2. Data passed to constructor
     * 3. Content file frontmatter
     * 4. Transient runtime keys (content, slug, template, parent)
     *
     * No validation occurs during load to allow loading pages with invalid data.
     * Validation happens when saving through `Page::write()` or setting fields
     */
    protected function load(): void
    {
        /**
         * @var array<string, array{path: string, filename: string, template: string}>
         */
        $contentFiles = [];

        /**
         * @var list<File>
         */
        $files = [];

        /**
         * @var list<string>
         */
        $languages = [];

        $config = $this->app()->config();

        $site = $this->site();

        if ($this->path !== null && FileSystem::isDirectory($this->path, assertExists: false)) {
            foreach (FileSystem::listFiles($this->path) as $file) {
                $name = FileSystem::name($file);

                $extension = '.' . FileSystem::extension($file);

                if ($extension === $config->get('system.pages.content.extension')) {
                    $language = '';

                    if (preg_match('/([a-z0-9]+)\.([a-z]+)/', $name, $matches)) {
                        // Parse double extension
                        [, $name, $language] = $matches;
                    }

                    if ($site->templates()->has($name)) {
                        $contentFiles[$language] = [
                            'path'     => FileSystem::joinPaths($this->path, $file),
                            'filename' => $file,
                            'template' => $name,
                        ];
                        if ($language !== '' && !in_array($language, $languages, true)) {
                            $languages[] = $language;
                        }
                    }
                } else {
                    if (Str::endsWith($file, $config->get('system.files.metadataExtension'))) {
                        continue;
                    }
                    if (in_array($extension, $config->get('system.files.allowedExtensions'), true)) {
                        $files[] = $this->app()->getService(FileFactory::class)->make(FileSystem::joinPaths($this->path, $file));
                    }
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
            $this->language ??= $key !== '' ? new Language($key) : null;

            $this->contentFile ??= new ContentFile($contentFiles[$key]['path']);

            $this->template ??= $site->templates()->get($contentFiles[$key]['template']);

            $this->scheme ??= $site->schemes()->get("pages.{$this->template}");
        } else {
            $this->template ??= $site->templates()->get('default');

            $this->scheme ??= $site->schemes()->get("pages.{$this->template}");
        }

        $this->fields ??= $this->scheme()->fields();
        $this->fields->setModel($this);

        $defaultLanguage = in_array((string) $site->languages()->default(), $languages, true)
            ? $site->languages()->default()
            : null;

        $this->languages ??= new Languages([
            'available' => $languages,
            'default'   => $defaultLanguage,
            'current'   => $this->language ?? null,
            'requested' => $site->languages()->requested(),
            'preferred' => $site->languages()->preferred(),
        ]);

        $this->files ??= (new FileCollection($files))->sort();

        $this->data = Arr::override(
            $this->defaults(),
            Arr::undot($this->data),
            Arr::undot($this->contentFile()?->frontmatter() ?? []),
            [
                'content'  => $this->contentFile?->content() ?? '',
                'slug'     => $this->slug ?? Str::slug($this->data['title'] ?? 'page'),
                'template' => $this->template,
                'parent'   => $this->parent() ?? $site,
            ],
        );

        $this->fields->setValues($this->data);

        $this->loaded = true;

        $this->app()->events()->dispatch(new PageLoadedEvent($this));
    }

    /**
     * Write page contents and move or copy files if needed
     *
     * @param string|null $language Language code to save the page in
     * @param bool        $copy     Whether to copy the page instead of moving it
     *
     * @throws UnexpectedValueException If parent or parent content path is missing
     * @throws InvalidValueException    If the language is invalid
     *
     * @since 2.2.0
     */
    protected function write(?string $language = null, bool $copy = false): void
    {
        if ($this->parent() === null) {
            throw new UnexpectedValueException('Unexpected missing parent');
        }

        if ($this->parent()->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing parent content path');
        }

        $config = $this->app()->config();

        $language ??= $this->language();

        if ($language !== null && !$this->site()->languages()->available()->has($language)) {
            throw new InvalidValueException('Invalid page language', 'invalidLanguage');
        }

        $frontmatter = $this->getFrontmatterData();

        $content = str_replace("\r\n", "\n", $this->data['content']);

        $contentTemplate = $this->contentFile() !== null
            ? Str::before(basename($this->contentFile()->path()), '.')
            : $this->template()->name();

        $this->setNum();

        $contentDir = $this->num()
            ? "{$this->num()}-{$this->slug()}"
            : $this->slug();

        $contentPath = FileSystem::joinPaths(
            (string) $this->parent()?->contentPath(),
            $contentDir . '/'
        );

        $differ = $contentPath !== $this->contentPath()
            || $contentTemplate !== $this->template->name()
            || $frontmatter !== $this->contentFile()?->frontmatter()
            || $content !== $this->contentFile()->content();

        if ($differ) {
            $filename = $this->template->name();

            if ($language !== null) {
                $filename .= ".{$language}";
            }

            $filename .= $config->get('system.pages.content.extension');

            $fileContent = Str::wrap(Yaml::encode($frontmatter), '---' . PHP_EOL) . $content;

            if ($contentPath !== $this->contentPath()) {
                if (!FileSystem::isDirectory($contentPath, assertExists: false)) {
                    FileSystem::createDirectory($contentPath, recursive: true);
                }
                if ($this->contentPath() !== null) {
                    if ($copy) {
                        FileSystem::copyDirectory($this->contentPath(), $contentPath, overwrite: FileSystem::isEmptyDirectory($contentPath, assertExists: false));
                    } else {
                        FileSystem::moveDirectory($this->contentPath(), $contentPath, overwrite: FileSystem::isEmptyDirectory($contentPath, assertExists: false));
                    }
                }
            } elseif ($contentTemplate !== $this->template->name() && $this->contentFile() !== null) {
                FileSystem::delete($this->contentFile()->path());
            }

            FileSystem::write($contentPath . $filename, $fileContent);

            $this->reload(['path' => $contentPath]);

            if ($this->site()->contentPath() !== null) {
                FileSystem::touch($this->site()->contentPath());
            }
        }
    }

    /**
     * Get frontmatter data for saving
     *
     * @return array<string, mixed>
     */
    protected function getFrontmatterData(): array
    {
        // Since `$this->data` already contains everything (including what was in the original frontmatter)
        // we just use the previous frontmatter to retain the key order and minimize the diff when saving
        $frontmatter = Arr::override(
            Arr::undot(array_fill_keys(array_keys($this->contentFile()?->frontmatter() ?? []), null)),
            Arr::undot($this->data)
        );

        // Remove ignored fields
        foreach ($this->fields() as $field) {
            if (in_array($field->name(), self::IGNORED_FIELD_NAMES, true)) {
                Arr::remove($frontmatter, $field->name());
            }

            if (in_array($field->type(), self::IGNORED_FIELD_TYPES, true)) {
                Arr::remove($frontmatter, $field->name());
            }
        }

        // Remove default values
        return Arr::exclude($frontmatter, $this->defaults());
    }

    /**
     * Set page path
     *
     * @throws UnexpectedValueException If site path is missing
     */
    protected function setPath(?string $path): void
    {
        if ($path === null) {
            $this->path = null;
            $this->relativePath = null;
            $this->route = null;
            $this->slug = null;
            return;
        }

        $this->path = FileSystem::normalizePath($path . '/');

        if ($this->site()->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing site path');
        }

        $this->relativePath = Str::prepend(Path::makeRelative($this->path, $this->site()->contentPath(), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

        $routePath = preg_replace('~[/\\\](\d+-)~', '/', $this->relativePath)
            ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));

        $this->route ??= Uri::normalize(Str::append($routePath, '/'));

        $this->slug ??= basename($this->route);
    }

    /**
     * Set page slug
     *
     * @throws InvalidValueException If the slug is invalid, for index or error pages, or if a page with the same route already exists
     */
    protected function setSlug(string $slug): void
    {
        if (!$this->validateSlug($slug)) {
            throw new InvalidValueException('Invalid page slug', 'invalidSlug');
        }
        if ($slug === $this->slug) {
            return;
        }
        if ($this->isIndexPage() || $this->isErrorPage()) {
            throw new InvalidValueException('Cannot change slug of index or error pages', 'indexOrErrorPageSlug');
        }
        if ($this->site()->findPage($this->parent()?->route() . $slug . '/') !== null) {
            throw new InvalidValueException('A page with the same route already exists', 'alreadyExists');
        }
        $this->slug = $slug;
    }

    /**
     * Set page num
     *
     * If no arguments are passed, the num is set based on the current mode
     */
    protected function setNum(?int $num = null): void
    {
        if (func_num_args() === 0) {
            $num = $this->num();

            $mode = $this->scheme()->options()->get('num');

            if ($mode === 'date') {
                $timestamp = isset($this->data['publishDate'])
                    ? Date::toTimestamp($this->data['publishDate'], [$this->app()->config()->get('system.date.dateFormat'), $this->app()->config()->get('system.date.datetimeFormat')])
                    : ($this->contentFile()?->lastModifiedTime() ?? time());
                $num = (int) date(self::DATE_NUM_FORMAT, $timestamp);
            } elseif ($this->parent() === null) {
                $num = null;
            } elseif ($this->contentPath() === null && $num === null) {
                $num = 1 + (int) max([0, ...$this->parent()->children()->everyItem()->num()->values()]);
            }
        }

        $this->num = $num;
    }

    /**
     * Set page parent
     *
     * @throws InvalidValueException If the parent is invalid
     */
    protected function setParent(Page|Site|string $parent): void
    {
        $previousParent = $this->parent();
        if ($parent instanceof Page || $parent instanceof Site) {
            $this->parent = $parent;
        } elseif ($parent === '.') {
            $this->parent = $this->site();
        } else {
            $this->parent = $this->site()->findPage($parent) ?? throw new InvalidValueException('Invalid parent', 'invalidParent');
        }
        if ($this->parent !== $previousParent && $this->isIndexOrErrorPage()) {
            throw new InvalidValueException('Cannot change parent of index or error pages', 'invalidParent');
        }
    }

    /**
     * Set page template
     *
     * @throws InvalidValueException If the template is invalid
     */
    protected function setTemplate(Template|string $template): void
    {
        if ($template instanceof Template) {
            $this->template = $template;
        } else {
            if (!$this->site()->templates()->has($template)) {
                throw new InvalidValueException('Invalid page template', 'invalidTemplate');
            }
            $this->template = $this->site()->templates()->get($template);
        }
        $this->scheme = $this->site()->schemes()->get("pages.{$template}");
    }

    /**
     * Set page language
     *
     * @throws InvalidValueException If the language is invalid
     */
    protected function setLanguage(Language|string|null $language): void
    {
        if ($language === null) {
            $this->language = null;
        }

        if (is_string($language)) {
            $language = new Language($language);
        }

        if (!$this->hasLoaded()) {
            $this->language = $language;
            return;
        }

        if ($this->languages()->current()?->code() !== ($code = $language?->code())) {
            if ($code !== null && !$this->languages()->available()->has($code)) {
                throw new InvalidValueException(sprintf('Invalid page language "%s"', $code), 'invalidLanguage');
            }
            $this->reload(['language' => $language]);
        }
    }

    /**
     * Set page metadata
     *
     * @since 2.2.0
     *
     * @param array<string, mixed>|MetadataCollection $metadata
     */
    protected function setMetadata(MetadataCollection|array $metadata): void
    {
        if ($metadata instanceof MetadataCollection) {
            $this->metadata = $metadata;
            $this->data['metadata'] = $metadata->toArray();
        } else {
            unset($this->metadata);
            $this->data['metadata'] = $metadata;
        }
    }

    /**
     * Set page taxonomy
     *
     * @param array<string, list<string>> $taxonomy
     *
     * @since 2.2.0
     */
    protected function setTaxonomy(array $taxonomy): void
    {
        if (!Arr::every($taxonomy, fn($terms, $taxonomyName) => is_string($taxonomyName)
            && is_array($terms) && Arr::every($terms, fn($term) => is_string($term)))) {
            throw new InvalidValueException('Invalid taxonomy format');
        }
        $this->data['taxonomy'] = $taxonomy;
    }

    /**
     * Set page HTTP response status
     *
     * @since 2.2.0
     */
    protected function setResponseStatus(ResponseStatus|int|null $responseStatus): void
    {
        if ($responseStatus === null) {
            unset($this->responseStatus, $this->data['responseStatus']);
            return;
        }

        if (is_int($responseStatus)) {
            $responseStatus = ResponseStatus::fromCode($responseStatus);
        }

        $this->responseStatus = $responseStatus;
        $this->data['responseStatus'] = $responseStatus->code();
    }

    /**
     * Reset page properties
     */
    protected function resetProperties(): void
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            unset($this->{$reflectionProperty->getName()});

            if ($reflectionProperty->hasDefaultValue()) {
                $this->{$reflectionProperty->getName()} = $reflectionProperty->getDefaultValue();
            }
        }
    }

    /**
     * Validate page slug helper
     */
    protected function validateSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_REGEX, $slug);
    }
}
