<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PageCollection::class)]
final class PageCollectionTest extends TestCase
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

    public function testFiltersExtractAndPaginationPreservePageCollectionType(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $about->set('listed', true);
        $about->set('taxonomy', ['tags' => ['PHP', 'CMS']]);
        $blog->set('published', false);
        $blog->set('routable', false);
        $blog->set('listed', false);

        $collection = $this->collection($about, $blog);

        $this->assertSame([$about->title(), $blog->title()], array_values($collection->extract('title')));
        $this->assertSame([$about], $collection->listed()->values());
        $this->assertSame([$about], $collection->published()->values());
        $this->assertSame([$about], $collection->routable()->values());
        $this->assertSame([$about, $blog], $collection->allowingChildren()->values());
        $this->assertSame([$about], $collection->havingTaxonomy(['tags' => ['php']], slug: true)->values());

        $paginated = $collection->paginate(1, 2);
        $this->assertSame([$blog], $paginated->values());
        $this->assertSame(2, $paginated->pagination()->pages());
        $this->assertSame(2, $paginated->pagination()->currentPage());
    }

    public function testSearchReturnsAnEmptyCollectionForShortQueries(): void
    {
        $collection = $this->collection($this->page('/about'));

        $this->assertTrue($collection->search('abc')->isEmpty());
        $this->assertTrue($collection->search('one two', minimumLength: 4)->isEmpty());
    }

    public function testSearchReturnsMatchingResultsInDescendingOrder(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $about->set('title', 'PHP guide');
        $blog->set('title', 'A PHP handbook for teams');

        $results = $this->collection($about, $blog)->search('PHP guide', minimumLength: 3, weights: [
            'title'   => 100,
            'summary' => 0,
            'content' => 0,
            'author'  => 0,
            'uri'     => 0,
        ]);

        $this->assertNotEmpty($results);
        $this->assertContains($about, $results);
        $this->assertContains($blog, $results);

        $this->assertSame([$about, $blog], $results->values());
    }

    public function testSearchExcludesPagesWithoutMatches(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $about->set('title', 'PHP guide');
        $blog->set('title', 'Cooking recipes');

        $results = $this->collection($about, $blog)->search('PHP guide', minimumLength: 3, weights: [
            'title'   => 100,
            'summary' => 0,
            'content' => 0,
            'author'  => 0,
            'uri'     => 0,
        ]);

        $this->assertSame([$about], $results->values());
    }

    public function testSearchDoesNotAddTransientScoresToTheSourcePages(): void
    {
        $about = $this->page('/about');
        $about->set('title', 'PHP guide');
        $this->assertFalse($about->has('score'));

        $this->collection($about)->search('PHP guide');

        $this->assertFalse($about->has('score'));
    }

    public function testSearchPreservesResultOrderAfterRepeatedExecution(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $about->set('title', 'PHP guide');
        $blog->set('title', 'PHP');
        $collection = $this->collection($about, $blog);

        $first = $collection->search('PHP', minimumLength: 3);
        $second = $collection->search('PHP', minimumLength: 3);

        $this->assertSame($first->values(), $second->values());
        $this->assertSame([$about, $blog], $first->values());
    }

    public function testPageRelationshipExclusionMethodsRemoveExpectedPages(): void
    {
        $parent = $this->page('/blog');
        $child = $this->page('/blog/hello-world');
        $about = $this->page('/about');
        $collection = $this->collection($parent, $child, $about);

        $this->assertSame([$parent, $about], $collection->withoutChildren($parent)->values());
        $this->assertSame([$about], $collection->withoutPageAndChildren($parent)->values());
        $this->assertSame([$parent, $about], $collection->withoutDescendants($parent)->values());
        $this->assertSame([$about], $collection->withoutPageAndDescendants($parent)->values());
        $this->assertSame([$child, $about], $collection->withoutParent($child)->values());
        $this->assertSame([$about], $collection->withoutPageAndParent($child)->values());
        $this->assertSame([$child, $about], $collection->withoutSiblings($about)->values());
    }

    public function testPaginationDoesNotMutateTheSourceCollection(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $collection = $this->collection($about, $blog);
        $before = $collection->values();

        $page = $collection->paginate(1, 2);

        $this->assertSame([$blog], $page->values());
        $this->assertSame($before, $collection->values());
    }

    public function testSortByReturnsANewSortedPageCollectionAndLeavesTheOriginalUntouched(): void
    {
        $about = $this->page('/about');
        $blog = $this->page('/blog');
        $collection = $this->collection($blog, $about);
        $before = $collection->values();

        $ascending = $collection->sortBy('title');
        $descending = $collection->sortBy('title', SORT_DESC);

        $this->assertInstanceOf(PageCollection::class, $ascending);
        $this->assertSame([$about, $blog], $ascending->values());
        $this->assertSame([$blog, $about], $descending->values());
        $this->assertSame($before, $collection->values());
    }

    public function testRelationshipFiltersPreservePageIdentityAndSourceCollection(): void
    {
        $parent = $this->page('/blog');
        $child = $this->page('/blog/hello-world');
        $about = $this->page('/about');
        $collection = $this->collection($parent, $child, $about);
        $before = $collection->values();

        $filtered = $collection->withoutPageAndDescendants($parent);

        $this->assertSame([$about], $filtered->values());
        $this->assertSame($before, $collection->values());
        $this->assertSame($parent, $collection->at(0));
        $this->assertSame($child, $collection->at(1));
    }

    private function page(string $route): Page
    {
        return $this->fixtureSite->findPage($route) ?? $this->fail("Missing page {$route}");
    }

    private function collection(Page ...$pages): PageCollection
    {
        $data = [];
        foreach ($pages as $page) {
            $data[$page->route()] = $page;
        }

        return $this->app->getService(PageCollectionFactory::class)->make($data);
    }

    private function temporarySite(): Site
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'page-collection-' . ++$this->temporarySiteCounter);
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/site', $path);

        return new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }
}
