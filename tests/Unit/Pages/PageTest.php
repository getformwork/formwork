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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Page::class)]
final class PageTest extends TestCase
{
    private App $app;

    private int $temporarySiteCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->app = App::instance();
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
        $this->assertNotNull($page->path());
        $this->assertNotNull($page->relativePath());
        $this->assertNotNull($page->contentFile());
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
        $this->assertIsString($defaults['content']);
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
        $page = $this->fixturePage('/about');

        $this->assertSame(1, $page->num());
        $this->assertSame('about', $page->slug());
        $this->assertSame('/about/', $page->route());
    }

    public function testTemplateLanguageAndContentFileAreResolvedFromTheFilename(): void
    {
        $page = $this->temporaryPage('localized', ['title' => 'Localized']);

        $this->assertSame('page', $page->template()->name());
        $this->assertNull($page->language());
        $this->assertSame([], $page->languages()->available()->toArray());
    }

    public function testMultilingualContentSelectsTheRequestedLanguageAndTracksAvailableVersions(): void
    {
        $site = $this->temporarySite();
        $path = rtrim((string) $site->contentPath(), '/') . '/localized/';
        mkdir($path, 0o777, true);
        file_put_contents($path . 'page.en.md', "---\ntitle: English\n---\nEnglish content");
        file_put_contents($path . 'page.it.md', "---\ntitle: Italiano\n---\nContenuto italiano");

        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $this->assertSame('it', $page->language()?->code());
        $this->assertSame('Italiano', $page->title());
        $this->assertSame('Contenuto italiano', trim(strip_tags($page->content())));
        $this->assertTrue($page->languages()->available()->has('en'));
        $this->assertTrue($page->languages()->available()->has('it'));
    }

    public function testInvalidLanguageIsRejectedAfterLoading(): void
    {
        $site = $this->temporarySite();
        $path = rtrim((string) $site->contentPath(), '/') . '/localized/';
        mkdir($path, 0o777, true);
        file_put_contents($path . 'page.en.md', "---\ntitle: English\n---\nEnglish content");
        file_put_contents($path . 'page.it.md', "---\ntitle: Italiano\n---\nContenuto italiano");
        $page = new Page(['site' => $site, 'path' => $path, 'language' => 'it'], $this->app);

        $this->expectException(InvalidValueException::class);
        $page->set('language', 'fr');
    }

    public function testIconFallsBackToTheSchemeAndCanBeOverridden(): void
    {
        $page = $this->temporaryPage('icon');
        $defaultIcon = $page->icon();

        $this->assertIsString($defaultIcon);
        $this->assertSame($defaultIcon, $page->icon());
    }

    public function testStatusHonoursPublishedAndDateWindows(): void
    {
        $page = $this->temporaryPage('status', ['published' => false]);
        $this->assertSame(Page::PAGE_STATUS_NOT_PUBLISHED, $page->status());

        $page = $this->temporaryPage('future', [
            'published'   => true,
            'publishDate' => "'" . date('Y-m-d', time() + 86400) . "'",
        ]);
        $this->assertFalse($page->isPublished());

        $page = $this->temporaryPage('expired', [
            'published'     => true,
            'unpublishDate' => "'" . date('Y-m-d', time() - 86400) . "'",
        ]);
        $this->assertFalse($page->isPublished());
    }

    public function testMetadataTaxonomyAndResponseStatusAreNormalized(): void
    {
        $page = $this->temporaryPage('data');

        $page->set('metadata', ['description' => 'A description']);
        $page->set('taxonomy', ['tags' => ['php', 'cms']]);
        $page->set('responseStatus', 404);

        $this->assertSame('A description', $page->metadata()->get('description')->content());
        $this->assertSame(['tags' => ['php', 'cms']], $page->taxonomy());
        $this->assertSame(ResponseStatus::fromCode(404), $page->responseStatus());
    }

    public function testCanonicalRouteIsNormalizedAndNullWhenEmpty(): void
    {
        $page = $this->temporaryPage('canonical');

        $this->assertNull($page->canonicalRoute());
        $page->set('canonicalRoute', 'docs//page/');
        $this->assertSame('docs/page/', $page->canonicalRoute());
        $page->set('canonicalRoute', null);
        $this->assertNull($page->canonicalRoute());
    }

    public function testInvalidSlugTemplateAndParentAreRejected(): void
    {
        $page = $this->temporaryPage('validation');

        $this->expectException(InvalidValueException::class);
        $page->set('slug', 'not a valid slug');
    }

    public function testInvalidTemplateIsRejected(): void
    {
        $page = $this->temporaryPage('template-validation');

        $this->expectException(InvalidValueException::class);
        $page->set('template', 'does-not-exist');
    }

    public function testParentCanBeResolvedByRouteAndInvalidParentIsRejected(): void
    {
        $site = $this->temporarySite();
        $parent = $this->temporaryPage('parent', site: $site);
        $child = $this->temporaryPage('child', site: $site);

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
        $page = $this->temporaryPage('invalid-taxonomy');

        $this->expectException($exception);
        $page->set('taxonomy', $taxonomy);
    }

    public function testTraversalMethodsExposeThePageTree(): void
    {
        $about = $this->fixturePage('/about');
        $blog = $this->fixturePage('/blog');
        $index = $this->fixturePage('/');

        $this->assertSame($this->app->site(), $about->parent());
        $this->assertTrue($about->isChildOf($this->app->site()));
        $this->assertTrue($about->isSiblingOf($blog));
        $this->assertTrue($about->isSiblingOf($index));
        $this->assertTrue($about->hasAncestors());
        $this->assertFalse($about->isAncestorOf($this->app->site()));
        $this->assertSame(0, $about->index());
        $this->assertSame(1, $about->level());
        $this->assertSame($blog, $about->nextSibling());
        $this->assertSame($about, $blog->previousSibling());
        $this->assertTrue($about->siblings()->contains($blog));
        $this->assertTrue($about->inclusiveSiblings()->contains($about));
    }

    public function testSiblingPredicateIsSymmetricAndExposesCurrentRegression(): void
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
        $empty = $this->temporaryPage('empty', frontmatter: []);
        $nonEmpty = $this->temporaryPage('non-empty', ['title' => 'Not empty']);
        $withoutContentFile = $this->temporaryPage('directory-only', createContentFile: false);

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
        $page = $this->temporaryPage('editable', ['title' => 'Before'], content: 'Before');

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
        $page = $this->temporaryPage('frontmatter', ['title' => 'Before']);
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
        $page = $this->temporaryPage('reloadable');
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
        $page = $this->temporaryPage('original', ['title' => 'Original']);
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
        $page = $this->temporaryPage('original', ['title' => 'Original']);
        $first = $page->duplicate();
        $second = $page->duplicate();

        $this->assertSame('original-copy', $first->slug());
        $this->assertSame('original-copy-2', $second->slug());
    }

    public function testDeleteCanRemoveOnlyTheCurrentLanguageOrAllLanguages(): void
    {
        $site = $this->temporarySite();
        $path = rtrim((string) $site->contentPath(), '/') . '/localized/';
        mkdir($path, 0o777, true);
        file_put_contents($path . 'page.en.md', "---\ntitle: English\n---\nEnglish content");
        file_put_contents($path . 'page.it.md', "---\ntitle: Italiano\n---\nContenuto italiano");
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
        $page = $this->temporaryPage('assets');
        $directory = (string) $page->contentPath();
        copy(ROOT_PATH . '/site/pages/index/formwork.png', $directory . '/photo.png');
        copy(ROOT_PATH . '/site/files/friday.mp4', $directory . '/clip.mp4');
        $this->writeSilentWav($directory . '/sound.mp3');

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
        $page = $this->temporaryPage('predicates');

        $this->assertTrue($page->hasContentFile());
        $this->assertFalse($page->isSite());
        $this->assertTrue($page->hasParent());
        $this->assertFalse($page->hasChildren());
        $this->assertFalse($page->hasDescendants());
        $this->assertTrue($page->hasSiblings());
        $this->assertNotNull($page->lastModifiedTime());
        $this->assertSame($page->files(), $page->files());
    }

    public function testLifecycleEventsAreDispatchedWithTheExpectedNames(): void
    {
        $names = [];
        foreach (
            [
                'pageLoaded',
                'pageBeforeSave',
                'pageAfterSave',
                'pageBeforeDuplicate',
                'pageAfterDuplicate',
                'pageBeforeDelete',
                'pageAfterDelete',
            ] as $name
        ) {
            $this->app->events()->on($name, function (object $event) use (&$names): void {
                $names[] = $event->name();
            });
        }

        $page = $this->temporaryPage('events');
        $page->save();
        $duplicate = $page->duplicate();
        $duplicate->delete();

        foreach (['pageLoaded', 'pageBeforeSave', 'pageAfterSave', 'pageBeforeDuplicate', 'pageAfterDuplicate', 'pageBeforeDelete', 'pageAfterDelete'] as $name) {
            $this->assertContains($name, $names);
        }
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

    private function fixturePage(string $route): Page
    {
        $page = $this->app->site()->findPage($route);

        return $page ?? $this->fail(sprintf('Fixture page %s was not found', $route));
    }

    /**
     * @param array<string, mixed> $frontmatter
     */
    private function temporaryPage(
        string $relativePath,
        array $frontmatter = ['title' => 'Temporary page'],
        string $content = 'Temporary content',
        bool $createContentFile = true,
        ?Site $site = null,
    ): Page {
        $site ??= $this->temporarySite();
        $path = rtrim((string) $site->contentPath(), '/') . '/' . trim($relativePath, '/') . '/';
        if (!is_dir($path)) {
            mkdir($path, 0o777, true);
        }

        if ($createContentFile) {
            $yaml = "---\n";
            foreach ($frontmatter as $key => $value) {
                $yaml .= sprintf("%s: %s\n", $key, is_bool($value) ? ($value ? 'true' : 'false') : $value);
            }
            file_put_contents($path . 'page.md', $yaml . "---\n" . $content);
        }

        return new Page(['site' => $site, 'path' => $path], $this->app);
    }

    private function temporarySite(): Site
    {
        $path = TESTS_TMP_PATH . '/pages-' . ++$this->temporarySiteCounter;
        mkdir($path, 0o777, true);
        mkdir($path . '/index', 0o777, true);
        file_put_contents($path . '/index/index.md', "---\ntitle: Temporary index\n---\n");
        mkdir($path . '/error', 0o777, true);
        file_put_contents($path . '/error/error.md', "---\ntitle: Temporary error\n---\n");

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }

    private function writeSilentWav(string $path): void
    {
        $sampleRate = 8000;
        $channels = 1;
        $bitsPerSample = 16;
        $data = str_repeat("\0", (int) ($sampleRate * $channels * ($bitsPerSample / 8) / 10));
        $header = 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVE';
        $header .= 'fmt ' . pack('VvvVVvv', 16, 1, $channels, $sampleRate, $sampleRate * $channels * 2, 2, $bitsPerSample);
        $header .= 'data' . pack('V', strlen($data));
        file_put_contents($path, $header . $data);
    }

    public static function invalidTaxonomyProvider(): iterable
    {
        yield 'scalar' => ['not-an-array', \TypeError::class];
        yield 'non-string taxonomy name' => [[1 => ['term']], InvalidValueException::class];
        yield 'non-list terms' => [['tags' => 'php'], InvalidValueException::class];
        yield 'non-string term' => [['tags' => [1]], InvalidValueException::class];
    }
}
