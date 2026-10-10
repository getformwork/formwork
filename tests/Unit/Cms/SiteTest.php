<?php

namespace Formwork\Tests\Unit\Cms;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Languages\Languages;
use Formwork\Pages\Exceptions\PageNotFoundException;
use Formwork\Pages\Page;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Site::class)]
final class SiteTest extends TestCase
{
    use BuildsPageSites;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->site = $this->siteFromFiles([
            'index/index.md'           => "---\ntitle: Home\n---\nHome\n",
            'error/error.md'           => "---\ntitle: Error\n---\nNot found\n",
            '1-first/page.md'          => "---\ntitle: First\n---\nFirst\n",
            '1-first/nested/page.md'   => "---\ntitle: Nested\n---\nNested\n",
            '2-second/page.md'         => "---\ntitle: Second\n---\nSecond\n",
            '_hidden/page.md'          => "---\ntitle: Hidden\n---\nHidden\n",
            'no-content/readme.txt'    => 'a directory without a content file',
            'empty-directory/.gitkeep' => '',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    // Identity

    public function testSiteIsASite(): void
    {
        $this->assertTrue($this->site->isSite());
        $this->assertFalse($this->site->isIndexPage());
        $this->assertFalse($this->site->isErrorPage());
        $this->assertFalse($this->site->isIndexOrErrorPage());
        $this->assertFalse($this->site->isDeletable());
        $this->assertSame('site', $this->site->getModelIdentifier());
        $this->assertSame($this->site, $this->site->site());
    }

    public function testRouteAndSlugOfTheSite(): void
    {
        $this->assertSame('/', $this->site->route());
        $this->assertSame('', $this->site->slug());
        $this->assertNull($this->site->canonicalRoute());
        $this->assertNull($this->site->parent());
    }

    public function testSiteIsStringifiedThroughItsTitle(): void
    {
        $site = $this->siteFromFiles([]);
        $site->set('title', 'My site');

        $this->assertSame('My site', (string) $site);
    }

    // Finding pages

    public function testPagesAreFoundByRoute(): void
    {
        $this->assertSame('First', $this->site->findPage('/first')?->title());
        $this->assertSame('First', $this->site->findPage('/first/')?->title());
        $this->assertSame('First', $this->site->findPage('first')?->title());
        $this->assertSame('Nested', $this->site->findPage('/first/nested')?->title());
    }

    public function testNumberPrefixesAreNotPartOfTheRoute(): void
    {
        $this->assertNotNull($this->site->findPage('/second'));
        $this->assertNull($this->site->findPage('/2-second')?->route());
        $this->assertNull($this->site->findPage('/1-first')?->route());
    }

    public function testTheRootRouteIsTheIndexPage(): void
    {
        $this->assertSame($this->site->indexPage(), $this->site->findPage('/'));
        $this->assertSame('/index/', $this->site->indexPage()->route());
    }

    public function testUnknownRoutesAreNotFound(): void
    {
        $this->assertNull($this->site->findPage('/missing')?->route());
        $this->assertNull($this->site->findPage('/first/missing')?->route());
        $this->assertNull($this->site->findPage('/missing/nested')?->route());
        $this->assertNull($this->site->findPage('')?->route());
        $this->assertNull($this->site->findPage('//')?->route());
        $this->assertNull($this->site->findPage('/first//nested')?->route());
    }

    public function testRoutesAreCaseSensitive(): void
    {
        $this->assertNull($this->site->findPage('/FIRST')?->route());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dotRoutes(): iterable
    {
        yield 'current directory' => ['/first/./nested'];
        yield 'parent directory' => ['/first/../second'];
        yield 'above the content' => ['/../'];
        yield 'dots only' => ['/..'];
        yield 'encoded dots' => ['/first/%2e%2e/second'];
        yield 'backslash' => ['/first\nested'];
        yield 'null byte' => ["/first\0"];
    }

    #[DataProvider('dotRoutes')]
    public function testRoutesWithDotSegmentsNeverEscapeTheContentTree(string $route): void
    {
        $this->assertNull($this->site->findPage($route)?->route());
    }

    public function testDirectoriesWithAnUnderscorePrefixAreNotReachable(): void
    {
        $this->assertNull($this->site->findPage('/_hidden')?->route(), 'Directories starting with an underscore are excluded from listings and must not be routable either');
    }

    public function testDirectoriesWithoutContentAreNotListed(): void
    {
        $routes = array_values($this->site->children()->everyItem()->route()->toArray());

        $this->assertNotContains('/no-content/', $routes);
        $this->assertNotContains('/empty-directory/', $routes);
        $this->assertNotContains('/_hidden/', $routes);
    }

    public function testPagesAreRetrievedOnce(): void
    {
        $path = $this->site->contentPath() . '1-first/';

        $this->assertSame($this->site->retrievePage($path), $this->site->retrievePage($path));
        $this->assertSame($this->site->findPage('/first'), $this->site->findPage('/first'));
    }

    public function testRetrievedPagesAreOrderedByPath(): void
    {
        $routes = array_values($this->site->retrievePages((string) $this->site->contentPath())->everyItem()->route()->toArray());

        $this->assertSame(array_values(array_filter($routes, fn($route) => $route !== null)), $routes);
        $this->assertSame(['/first/', '/second/'], array_slice($routes, 0, 2));
    }

    public function testRecursiveRetrievalIncludesNestedPages(): void
    {
        $content = (string) $this->site->contentPath();

        $flat = array_values($this->site->retrievePages($content)->everyItem()->route()->toArray());
        $recursive = array_values($this->site->retrievePages($content, recursive: true)->everyItem()->route()->toArray());

        $this->assertNotContains('/first/nested/', $flat);
        $this->assertContains('/first/nested/', $recursive);
    }

    public function testPagesAndChildrenAgree(): void
    {
        $this->assertSame($this->site->children(), $this->site->pages());
        $this->assertTrue($this->site->hasPages());
        $this->assertFalse($this->siteFromFiles([])->hasPages());
    }

    // Index and error pages

    public function testErrorPage(): void
    {
        $this->assertSame('/error/', $this->site->errorPage()->route());
    }

    public function testMissingIndexPageIsReported(): void
    {
        $site = $this->siteFromFiles(['first/page.md' => "---\ntitle: First\n---\n"]);

        $this->expectException(PageNotFoundException::class);

        $site->indexPage();
    }

    public function testMissingErrorPageIsReported(): void
    {
        $site = $this->siteFromFiles(['first/page.md' => "---\ntitle: First\n---\n"]);

        $this->expectException(PageNotFoundException::class);

        $site->errorPage();
    }

    // Current page

    public function testCurrentPageIsUnsetByDefaultAndCanBeSet(): void
    {
        $this->assertNull($this->site->currentPage());

        $page = $this->pageAt($this->site, '/first');

        $this->assertSame($page, $this->site->setCurrentPage($page));
        $this->assertSame($page, $this->site->currentPage());
    }

    // Route aliases

    public function testRouteAliasesAreNormalized(): void
    {
        $site = $this->siteFromFiles([]);
        $site->set('routeAliases', ['/old-url/' => 'new-url', 'plain' => '/target/']);

        $this->assertSame(['old-url' => '/new-url/', 'plain' => '/target/'], $site->routeAliases());
        $this->assertSame('/new-url/', $site->resolveRouteAlias('old-url'));
        $this->assertNull($site->resolveRouteAlias('unknown'));
    }

    // Modification time

    public function testLastModifiedTimeIsThatOfTheContentDirectory(): void
    {
        touch((string) $this->site->contentPath(), 1_700_000_000);

        $this->assertSame(1_700_000_000, $this->site->lastModifiedTime());
    }

    public function testModifiedSinceLooksIntoNestedFiles(): void
    {
        $content = (string) $this->site->contentPath();
        $old = time() - 1000;
        $this->touchTree($content, $old);

        $this->assertFalse($this->site->modifiedSince($old + 10));

        touch($content . '1-first/nested/page.md', time());
        touch($content . '1-first/nested/', time());
        touch($content . '1-first/', time());

        $this->assertTrue($this->site->modifiedSince($old + 10));
    }

    // Metadata

    public function testMetadataIncludesTheDefaultsAndTheConfiguredValues(): void
    {
        $site = $this->siteFromFiles([]);
        $site->set('metadata', ['description' => 'A site', 'robots' => 'noindex']);

        $metadata = $site->metadata();

        $this->assertSame('A site', $metadata->get('description')?->content());
        $this->assertSame('noindex', $metadata->get('robots')?->content());
        $this->assertNotNull($metadata->get('charset'));
    }

    public function testMetadataIsBuiltOnce(): void
    {
        $this->assertSame($this->site->metadata(), $this->site->metadata());
    }

    // Misc

    public function testSchemesAndLanguagesComeFromTheApplication(): void
    {
        $this->assertSame(App::instance()->schemes(), $this->site->schemes());
        $this->assertSame('config.site', $this->site->scheme()->id());
        $this->assertSame(App::instance()->getService(Languages::class), $this->site->languages());
    }

    public function testFindPageReturnsPageInstances(): void
    {
        $this->assertContainsOnlyInstancesOf(Page::class, [$this->site->findPage('/first'), $this->site->indexPage()]);
    }

    private function touchTree(string $path, int $time): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            touch($item->getPathname(), $time);
        }
        touch($path, $time);
    }
}
