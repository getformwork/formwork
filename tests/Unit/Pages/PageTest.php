<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Page::class)]
final class PageTest extends TestCase
{
    private App $app;

    private Site $fixtureSite;

    private int $temporarySiteCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->app = App::instance();
        $this->fixtureSite = $this->temporarySite();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testLoadsFixturePageAndExposesCoreProperties(): void
    {
        $page = $this->fixturePage('/about');

        $this->assertTrue($page->hasLoaded());
        $this->assertSame('page', $page->getModelIdentifier());
        $this->assertSame('about', $page->slug());
        $this->assertSame('/about/', $page->route());
        $this->assertStringEndsWith('/about/', $page->uri('', false));
        $this->assertStringEndsWith('/about/', (string) $page->path());
        $this->assertSame('/about/', $page->relativePath());
        $this->assertSame($page->path(), $page->contentPath());
        $this->assertSame('page.md', basename((string) $page->contentFile()?->path()));
        $this->assertNull($page->num());
        $this->assertSame(1, $page->level());
        $this->assertSame($page->title(), (string) $page);
        $this->assertSame($page, $page->site()->findPage('/about'));
    }

    public function testDefaultsContainStablePageDefaults(): void
    {
        $defaults = $this->fixturePage('/about')->defaults();

        $this->assertTrue($defaults['published']);
        $this->assertTrue($defaults['routable']);
        $this->assertTrue($defaults['searchable']);
        $this->assertTrue($defaults['cacheable']);
        $this->assertTrue($defaults['allowChildren']);
        $this->assertSame(200, $defaults['responseStatus']);
        $this->assertSame([], $defaults['metadata']);
        $this->assertSame([], $defaults['taxonomy']);
        $this->assertSame('', $defaults['content']);
        $this->assertSame([], $defaults['headers']);
        $this->assertNull($defaults['canonicalRoute']);
        $this->assertNull($defaults['publishDate']);
        $this->assertNull($defaults['unpublishDate']);
    }

    public function testPagesWithoutANumberAreNeitherListedNorOrderableByDefault(): void
    {
        $defaults = $this->fixturePage('/about')->defaults();

        $this->assertNull($this->fixturePage('/about')->num());
        $this->assertFalse($defaults['listed']);
        $this->assertFalse($defaults['orderable']);
    }

    public function testNumberedPagesAreListedAndOrderableByDefault(): void
    {
        $page = $this->temporaryPage('1-numbered', $this->temporarySite(__DIR__ . '/Fixtures/numbered-site'));
        $defaults = $page->defaults();

        $this->assertSame(1, $page->num());
        $this->assertTrue($defaults['listed']);
        $this->assertTrue($defaults['orderable']);
    }

    public function testPageWithoutAPathGetsUnroutableDefaults(): void
    {
        $page = new Page(['site' => $this->temporarySite()], $this->app);
        $defaults = $page->defaults();

        $this->assertNull($page->path());
        $this->assertNull($page->route());
        $this->assertFalse($defaults['routable']);
        $this->assertFalse($defaults['cacheable']);
        $this->assertFalse($defaults['listed']);
        $this->assertFalse($defaults['orderable']);
        $this->assertTrue($page->isEmpty());
    }

    public function testGetHasAndSetWorkForFrontmatterAndRuntimeFields(): void
    {
        $page = $this->fixturePage('/about');

        $this->assertTrue($page->has('title'));
        $this->assertSame($page->title(), $page->get('title'));
        $this->assertSame('fallback', $page->get('missing', 'fallback'));

        $page->set('title', 'Changed title');
        $page->set('customRuntimeValue', 'value');

        $this->assertSame('Changed title', $page->title());
        $this->assertSame('value', $page->get('customRuntimeValue'));
    }

    public function testNumberPrefixAndSlugAreSeparated(): void
    {
        $page = $this->temporaryPage('1-numbered', $this->temporarySite(__DIR__ . '/Fixtures/numbered-site'));

        $this->assertSame(1, $page->num());
        $this->assertSame('numbered', $page->slug());
        $this->assertSame('/numbered/', $page->route());
    }

    public function testTemplateLanguageAndContentFileAreResolvedFromTheFilename(): void
    {
        $page = $this->temporaryPage('about');

        $this->assertSame('page', $page->template()->name());
        $this->assertSame('page.md', basename((string) $page->contentFile()?->path()));
        $this->assertNull($page->language());
        $this->assertSame([], $page->languages()->available()->toArray());
    }

    public function testMultilingualContentSelectsTheRequestedLanguageAndTracksAvailableVersions(): void
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'localized') . '/';

        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $this->assertSame('it', $page->language()?->code());
        $this->assertSame('page.it.md', basename((string) $page->contentFile()?->path()));
        $this->assertSame('Italiano', $page->title());
        $this->assertSame('Questa pagina spiega come è organizzato il sito e dove trovare le sezioni principali.', trim(strip_tags($page->content())));
        $this->assertTrue($page->languages()->available()->has('en'));
        $this->assertTrue($page->languages()->available()->has('it'));
    }

    public function testInvalidLanguageIsRejectedAfterLoading(): void
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'localized') . '/';
        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $this->expectException(InvalidValueException::class);
        $page->set('language', 'fr');
    }

    public function testIconIsReadFromTheSchemeAndCanBeOverridden(): void
    {
        $page = $this->temporaryPage('blog');

        $this->assertSame('page-listing', $page->icon());
        $this->assertSame('page', $this->temporaryPage('about')->icon());

        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'about') . '/';
        FileSystem::write($path . 'page.md', "---\ntitle: About\nicon: custom-icon\n---\n\nContent\n");
        $custom = new Page(['site' => $site, 'path' => $path], $this->app);

        $this->assertSame('custom-icon', $custom->icon());
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('statusProvider')]
    public function testStatusHonoursPublishedAndDateWindows(array $data, string $expectedStatus): void
    {
        $page = $this->temporaryPage('about');

        foreach ($data as $key => $value) {
            $page->set($key, $value);
        }

        $this->assertSame($expectedStatus, $page->status());
        $this->assertSame($expectedStatus === Page::PAGE_STATUS_PUBLISHED, $page->isPublished());
    }

    public function testMetadataTaxonomyAndResponseStatusAreNormalized(): void
    {
        $page = $this->temporaryPage('about');

        $page->set('metadata', ['description' => 'A description']);
        $page->set('taxonomy', ['tags' => ['php', 'cms']]);
        $page->set('responseStatus', 404);

        $this->assertSame('A description', $page->metadata()->get('description')->content());
        $this->assertSame(['tags' => ['php', 'cms']], $page->taxonomy());
        $this->assertSame(ResponseStatus::fromCode(404), $page->responseStatus());
    }

    public function testCanonicalRouteIsNormalizedAndNullWhenEmpty(): void
    {
        $page = $this->temporaryPage('about');

        $this->assertNull($page->canonicalRoute());
        $page->set('canonicalRoute', 'docs//page/');
        $this->assertSame('docs/page/', $page->canonicalRoute());
        $page->set('canonicalRoute', null);
        $this->assertNull($page->canonicalRoute());
    }

    #[DataProvider('invalidSlugProvider')]
    public function testInvalidSlugIsRejected(string $slug): void
    {
        $page = $this->temporaryPage('about');

        try {
            $page->set('slug', $slug);
            $this->fail(sprintf('Slug "%s" should have been rejected', $slug));
        } catch (InvalidValueException) {
            $this->assertSame('about', $page->slug());
        }
    }

    #[DataProvider('validSlugProvider')]
    public function testValidSlugIsAccepted(string $slug): void
    {
        $page = $this->temporaryPage('about');

        $page->set('slug', $slug);

        $this->assertSame($slug, $page->slug());
    }

    public function testInvalidTemplateIsRejected(): void
    {
        $page = $this->temporaryPage('about');

        $this->expectException(InvalidValueException::class);
        $page->set('template', 'does-not-exist');
    }

    public function testParentCanBeResolvedByRouteAndInvalidParentIsRejected(): void
    {
        $site = $this->temporarySite();
        $parent = $this->temporaryPage('parent', site: $site);
        $child = $this->temporaryPage('about', site: $site);

        $child->set('parent', '.');
        $this->assertSame($site, $child->parent());
        $child->set('parent', $parent);
        $this->assertSame($parent, $child->parent());

        $this->expectException(InvalidValueException::class);
        $child->set('parent', '/missing-parent');
    }

    #[DataProvider('invalidTaxonomyProvider')]
    public function testRejectsInvalidTaxonomy(mixed $taxonomy, string $exception): void
    {
        $page = $this->temporaryPage('about');

        $this->expectException($exception);
        $page->set('taxonomy', $taxonomy);
    }

    public function testTraversalMethodsExposeThePageTree(): void
    {
        $about = $this->fixturePage('/about');
        $blog = $this->fixturePage('/blog');
        $index = $this->fixturePage('/');

        $this->assertSame($this->fixtureSite, $about->parent());
        $this->assertTrue($about->isChildOf($this->fixtureSite));
        $this->assertTrue($about->isSiblingOf($blog));
        $this->assertTrue($about->isSiblingOf($index));
        $this->assertTrue($about->hasAncestors());
        $this->assertFalse($about->isAncestorOf($this->fixtureSite));
        $this->assertSame(0, $about->index());
        $this->assertSame(1, $about->level());
        $this->assertSame($blog, $about->nextSibling());
        $this->assertSame($about, $blog->previousSibling());
        $this->assertTrue($about->siblings()->contains($blog));
        $this->assertTrue($about->inclusiveSiblings()->contains($about));
    }

    public function testSiblingPredicateIsSymmetric(): void
    {
        $about = $this->fixturePage('/about');
        $blog = $this->fixturePage('/blog');

        $this->assertTrue($about->isSiblingOf($blog));
        $this->assertTrue($blog->isSiblingOf($about));
        $this->assertTrue($about->isSiblingOf($this->fixturePage('/')));
        $this->assertFalse($about->isSiblingOf($this->fixturePage('/blog/hello-world')));
    }

    public function testIsEmptyIsTrueOnlyWhenThereIsNoFrontmatter(): void
    {
        $empty = $this->temporaryPage('empty');
        $nonEmpty = $this->temporaryPage('about');
        $withoutContentFile = $this->temporaryPage('directory-only');

        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($nonEmpty->isEmpty());
        $this->assertTrue($withoutContentFile->isEmpty());
    }

    public function testUidIsStableAndDerivedFromTheRelativePath(): void
    {
        $page = $this->fixturePage('/about');

        $this->assertSame($page->uid(), $page->uid());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}(?:-[a-f0-9]{8}){3}$/', $page->uid());
        $this->assertNotSame($page->uid(), $this->fixturePage('/blog')->uid());
    }

    public function testUriUsesCanonicalRouteAndAbsoluteRequestUri(): void
    {
        $page = $this->fixturePage('/about');

        $this->assertStringEndsWith('/about/team/', $page->uri('team', false));
        $page->set('canonicalRoute', '/company/about');
        $this->assertStringEndsWith('/company/about/team/', $page->uri('team', false));
    }

    public function testSaveMovesAndReloadsThePageFromDisk(): void
    {
        $page = $this->temporaryPage('about');

        $page->set('slug', 'renamed');
        $page->set('title', 'After');
        $page->set('content', 'After content');
        $page->save();

        $this->assertSame('renamed', $page->slug());
        $this->assertSame('After', $page->title());
        $this->assertSame('After content', trim($page->contentFile()?->content()));
        $this->assertDirectoryExists((string) $page->contentPath());
        $this->assertFileExists((string) $page->contentFile()?->path());
    }

    public function testSaveOmitsRuntimeFieldsFromFrontmatter(): void
    {
        $page = $this->temporaryPage('about');
        $page->set('title', 'After');
        $page->set('slug', 'renamed');
        $page->set('template', 'page');
        $page->set('parent', '.');
        $page->save();

        $frontmatter = $page->contentFile()?->frontmatter();
        $this->assertIsArray($frontmatter);
        $this->assertSame('After', $frontmatter['title']);
        $this->assertArrayNotHasKey('content', $frontmatter);
        $this->assertArrayNotHasKey('slug', $frontmatter);
        $this->assertArrayNotHasKey('template', $frontmatter);
        $this->assertArrayNotHasKey('parent', $frontmatter);
    }

    public function testSaveRejectsAPageWithoutAParent(): void
    {
        $site = $this->temporarySite();
        $page = new Page(['site' => $site], $this->app);

        $this->expectException(\UnexpectedValueException::class);
        $page->save();
    }

    public function testReloadRejectsAnUnloadedPage(): void
    {
        $page = $this->temporaryPage('about');
        $page->reload();
        $this->assertTrue($page->hasLoaded());

        $reflection = new \ReflectionClass($page);
        $loaded = $reflection->getProperty('loaded');
        $loaded->setValue($page, false);

        $this->expectException(RuntimeException::class);
        $page->reload();
    }

    public function testDuplicateCreatesAnIndependentCopyAndDeleteRemovesIt(): void
    {
        $page = $this->temporaryPage('original');
        $duplicate = $page->duplicate(['title' => 'Copy']);

        $this->assertNotSame($page, $duplicate);
        $this->assertSame('original-copy', $duplicate->slug());
        $this->assertSame('Copy', $duplicate->title());
        $this->assertDirectoryExists((string) $duplicate->contentPath());
        $this->assertTrue($duplicate->isDeletable());

        $path = $duplicate->contentPath();
        $duplicate->delete();
        $this->assertDirectoryDoesNotExist((string) $path);
    }

    public function testDuplicateGeneratesTheNextAvailableCopySlug(): void
    {
        $page = $this->temporaryPage('original');
        $first = $page->duplicate();
        $second = $page->duplicate();

        $this->assertSame('original-copy', $first->slug());
        $this->assertSame('original-copy-2', $second->slug());
    }

    public function testDeleteCanRemoveOnlyTheCurrentLanguageOrAllLanguages(): void
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'localized') . '/';
        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $page->delete();
        $this->assertFileExists($path . 'page.en.md');
        $this->assertFileDoesNotExist($path . 'page.it.md');

        $page->delete(allLanguages: true);
        $this->assertDirectoryDoesNotExist($path);
    }

    public function testPageWithChildrenCannotBeDuplicatedOrDeleted(): void
    {
        $site = $this->temporarySite();
        $parent = $this->temporaryPage('parent', site: $site);
        $this->temporaryPage('parent/child', site: $site);

        $this->assertFalse($parent->isDuplicable());
        $this->assertFalse($parent->isDeletable());
        $this->expectException(RuntimeException::class);
        $parent->delete();
    }

    public function testFilesAndMediaCollectionsAreConsistent(): void
    {
        $page = $this->temporaryPage('about');
        $config = $this->app->config();
        $pagesPath = $config->getString('system.pages.path');
        $config->set('system.pages.path', TESTS_TMP_PATH);
        try {
            $page->reload();

            $this->assertCount(3, $page->files());
            $this->assertCount(1, $page->images());
            $this->assertCount(1, $page->videos());
            $this->assertCount(1, $page->audios());
            $this->assertCount(3, $page->media());
        } finally {
            $config->set('system.pages.path', $pagesPath);
        }
    }

    public function testIndexAndErrorPagesAreProtected(): void
    {
        $index = $this->fixturePage('/');
        $error = $this->fixturePage('/error');

        $this->assertTrue($index->isIndexPage());
        $this->assertTrue($error->isErrorPage());
        $this->assertTrue($index->isIndexOrErrorPage());
        $this->assertFalse($index->isDeletable());
        $this->assertFalse($error->isDeletable());
    }

    public function testContentAndFilePredicatesRemainCoherentAfterReload(): void
    {
        $page = $this->temporaryPage('about');

        $this->assertTrue($page->hasContentFile());
        $this->assertFalse($page->isSite());
        $this->assertTrue($page->hasParent());
        $this->assertFalse($page->hasChildren());
        $this->assertFalse($page->hasDescendants());
        $this->assertTrue($page->hasSiblings());
        $this->assertSame(filemtime((string) $page->contentFile()?->path()), $page->lastModifiedTime());
        $this->assertSame($page->files(), $page->files());
    }

    public function testLifecycleEventsAreDispatchedInOrderWithTheExpectedPages(): void
    {
        $page = $this->temporaryPage('original');

        /** @var list<array{string, Page}> $events */
        $events = [];
        foreach (
            [
                'pageBeforeSave',
                'pageAfterSave',
                'pageBeforeDuplicate',
                'pageAfterDuplicate',
                'pageBeforeDelete',
                'pageAfterDelete',
            ] as $name
        ) {
            $this->app->events()->on($name, function (object $event) use (&$events): void {
                $events[] = [$event->name(), $event->page()];
            });
        }

        $page->save();
        $duplicate = $page->duplicate();
        $duplicate->delete();

        $this->assertSame(
            ['pageBeforeSave', 'pageAfterSave', 'pageBeforeDuplicate', 'pageAfterDuplicate', 'pageBeforeDelete', 'pageAfterDelete'],
            array_column($events, 0),
        );

        // Save and duplicate events refer to the original page, delete events to the one being deleted
        $this->assertSame(
            [$page, $page, $page, $page, $duplicate, $duplicate],
            array_column($events, 1),
        );
    }

    public function testPageLoadedEventIsDispatchedWhenAPageIsLoaded(): void
    {
        $loaded = [];
        $this->app->events()->on('pageLoaded', function (object $event) use (&$loaded): void {
            $loaded[] = $event->page();
        });

        $page = $this->temporaryPage('about');

        $this->assertContains($page, $loaded);
    }

    public function testRenderDispatchesPageRenderEventAndReturnsMarkup(): void
    {
        $page = $this->fixturePage('/about');
        $renderedPage = null;
        $this->app->events()->on('pageRender', function (object $event) use (&$renderedPage): void {
            $renderedPage = $event->page();
        });

        $output = $page->render();

        $this->assertSame($page, $renderedPage);
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('About', $output);
    }

    public function testChangingSlugKeepsPathIdentityUntilSave(): void
    {
        $page = $this->temporaryPage('about');
        $path = $page->path();

        $page->set('slug', 'renamed');

        $this->assertSame($path, $page->path());
        $this->assertSame('renamed', $page->slug());
    }

    public function testReloadReconstructsAllDerivedAndCachedStateFromDisk(): void
    {
        $page = $this->temporaryPage('about');
        $page->metadata();
        $path = $page->contentFile()?->path();
        $this->assertNotNull($path);

        FileSystem::write($path, "---\ntitle: Reloaded\nmetadata:\n  description: Fresh\n---\nFresh content\n");

        $page->reload();

        $this->assertSame('Reloaded', $page->title());
        $this->assertSame('Fresh content', $page->contentFile()?->content());
        $this->assertSame('Fresh', $page->metadata()->get('description')->content());
    }

    public function testReloadDiscardsLazilyCachedStatusAndModificationTime(): void
    {
        $page = $this->temporaryPage('about');
        $file = (string) $page->contentFile()?->path();
        $modifiedBefore = $page->lastModifiedTime();

        $this->assertSame(Page::PAGE_STATUS_PUBLISHED, $page->status());
        $this->assertIsInt($modifiedBefore);

        FileSystem::write($file, "---\ntitle: About\npublished: false\n---\nContent\n");
        touch($file, $modifiedBefore + 1000);
        $page->reload();

        $this->assertSame(Page::PAGE_STATUS_NOT_PUBLISHED, $page->status());
        $this->assertSame($modifiedBefore + 1000, $page->lastModifiedTime());
    }

    public function testDuplicateDoesNotShareMutableMetadataStateWithTheSource(): void
    {
        $page = $this->temporaryPage('about');
        $page->set('metadata', ['description' => 'Original']);
        $originalMetadata = $page->metadata();

        $duplicate = $page->duplicate();
        $duplicate->metadata()->set('description', 'Duplicate');

        $this->assertSame('Original', $originalMetadata->get('description')->content());
        $this->assertSame('Duplicate', $duplicate->metadata()->get('description')->content());
    }

    public function testDuplicateGetsAnIndependentDataSet(): void
    {
        $page = $this->temporaryPage('original');
        $originalTitle = $page->title();
        $duplicate = $page->duplicate();

        $this->assertSame($originalTitle, $duplicate->title());

        $duplicate->set('title', 'Duplicate');
        $page->set('published', false);

        $this->assertSame($originalTitle, $page->title());
        $this->assertSame('Duplicate', $duplicate->title());
        $this->assertTrue($duplicate->get('published', true));
    }

    public function testDuplicateFillsTheFirstAvailableCopySlug(): void
    {
        $page = $this->temporaryPage('original');
        $first = $page->duplicate();
        $second = $page->duplicate();
        $first->delete();
        $third = $page->duplicate();

        $this->assertSame('original-copy', $third->slug());
        $this->assertSame($third->contentPath(), $first->contentPath());
        $this->assertSame('original-copy-2', $second->slug());
    }

    public function testSaveReloadRoundTripPreservesThePersistedState(): void
    {
        $page = $this->temporaryPage('about');
        $page->setMultiple([
            'slug'    => 'renamed',
            'title'   => 'Persisted title',
            'content' => 'Persisted content',
        ]);
        $page->save();
        $page->reload();

        $this->assertSame('renamed', $page->slug());
        $this->assertSame('/renamed/', $page->route());
        $this->assertSame('Persisted title', $page->title());
        $this->assertSame('Persisted content', $page->contentFile()?->content());
    }

    public function testChangingLanguageReloadsTheCorrespondingContentVersion(): void
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'localized') . '/';
        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $this->assertSame('it', $page->language()?->code());
        $page->set('language', 'en');

        $this->assertSame('en', $page->language()?->code());
        $this->assertSame('English', $page->title());
    }

    public function testInvalidLanguageChangeLeavesTheCurrentLanguageUntouched(): void
    {
        $site = $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), 'localized') . '/';
        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        try {
            $page->set('language', 'fr');
            $this->fail('The invalid language should have been rejected.');
        } catch (InvalidValueException) {
            // Expected.
        }

        $this->assertSame('it', $page->language()?->code());
        $this->assertSame('Italiano', $page->title());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function statusProvider(): iterable
    {
        yield 'published by default' => [[], Page::PAGE_STATUS_PUBLISHED];
        yield 'explicitly published' => [['published' => true], Page::PAGE_STATUS_PUBLISHED];
        yield 'explicitly not published' => [['published' => false], Page::PAGE_STATUS_NOT_PUBLISHED];
        yield 'publish date in the past' => [['publishDate' => '2000-01-01'], Page::PAGE_STATUS_PUBLISHED];
        yield 'publish date in the future' => [['publishDate' => '2099-01-01'], Page::PAGE_STATUS_NOT_PUBLISHED];
        yield 'unpublish date in the future' => [['unpublishDate' => '2099-01-01'], Page::PAGE_STATUS_PUBLISHED];
        yield 'unpublish date in the past' => [['unpublishDate' => '2000-01-01'], Page::PAGE_STATUS_NOT_PUBLISHED];
        yield 'inside the publication window' => [
            ['publishDate' => '2000-01-01', 'unpublishDate' => '2099-01-01'],
            Page::PAGE_STATUS_PUBLISHED,
        ];
        yield 'window already closed' => [
            ['publishDate' => '2000-01-01', 'unpublishDate' => '2001-01-01'],
            Page::PAGE_STATUS_NOT_PUBLISHED,
        ];
        yield 'window not opened yet' => [
            ['publishDate' => '2098-01-01', 'unpublishDate' => '2099-01-01'],
            Page::PAGE_STATUS_NOT_PUBLISHED,
        ];
        yield 'not published overrides a valid window' => [
            ['published' => false, 'publishDate' => '2000-01-01', 'unpublishDate' => '2099-01-01'],
            Page::PAGE_STATUS_NOT_PUBLISHED,
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSlugProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['not a valid slug'];
        yield 'underscore' => ['under_score'];
        yield 'leading hyphen' => ['-slug'];
        yield 'trailing hyphen' => ['slug-'];
        yield 'consecutive hyphens' => ['a--b'];
        yield 'slash' => ['a/b'];
        yield 'accented character' => ['perché'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validSlugProvider(): iterable
    {
        yield 'lowercase' => ['renamed'];
        yield 'mixed case' => ['Mixed-Case'];
        yield 'digits only' => ['2024'];
        yield 'hyphenated' => ['my-new-page-2'];
    }

    public static function invalidTaxonomyProvider(): iterable
    {
        yield 'scalar' => ['not-an-array', \TypeError::class];
        yield 'non-string taxonomy name' => [[1 => ['term']], InvalidValueException::class];
        yield 'non-list terms' => [['tags' => 'php'], InvalidValueException::class];
        yield 'non-string term' => [['tags' => [1]], InvalidValueException::class];
    }

    private function fixturePage(string $route): Page
    {
        $page = $this->fixtureSite->findPage($route);

        return $page ?? $this->fail(sprintf('Fixture page %s was not found', $route));
    }

    private function temporaryPage(string $relativePath, ?Site $site = null): Page
    {
        $site ??= $this->temporarySite();
        $path = FileSystem::joinPaths((string) $site->contentPath(), trim($relativePath, '/')) . '/';
        FileSystem::assertExists($path);

        return new Page(['site' => $site, 'path' => $path], $this->app);
    }

    private function temporarySite(string $fixturePath = __DIR__ . '/Fixtures/site'): Site
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'pages-' . ++$this->temporarySiteCounter);
        FileSystem::copyDirectory($fixturePath, $path);

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }
}
