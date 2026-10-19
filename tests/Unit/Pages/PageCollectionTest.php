<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PageCollection::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PageCollectionTest extends TestCase
{
    use BuildsPageSites;

    private App $app;

    private Site $fixtureSite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();
        $this->fixtureSite = $this->siteFromFixture();
    }

    public function testFiltersExtractAndPaginationPreservePageCollectionType(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $page->set('listed', true);
        $page->set('taxonomy', ['tags' => ['PHP', 'CMS']]);
        $blog->set('published', false);
        $blog->set('routable', false);
        $blog->set('listed', false);

        $collection = $this->collection($page, $blog);

        $this->assertSame([$page->get('title'), $blog->get('title')], array_values($collection->extract('title')));
        $this->assertSame([$page], $collection->listed()->values());
        $this->assertSame([$page], $collection->published()->values());
        $this->assertSame([$page], $collection->routable()->values());
        $this->assertSame([$page, $blog], $collection->allowingChildren()->values());
        $this->assertSame([$page], $collection->havingTaxonomy(['tags' => ['php']], slug: true)->values());

        $pageCollection = $collection->paginate(1, 2);
        $this->assertSame([$blog], $pageCollection->values());
        $this->assertSame(2, $pageCollection->pagination()->pages());
        $this->assertSame(2, $pageCollection->pagination()->currentPage());
    }

    public function testSearchReturnsAnEmptyCollectionForShortQueries(): void
    {
        $collection = $this->collection($this->page('/about'));

        $this->assertTrue($collection->search('abc')->isEmpty());
        $this->assertTrue($collection->search('one two', minimumLength: 4)->isEmpty());
    }

    public function testSearchReturnsMatchingResultsInDescendingOrder(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $page->set('title', 'PHP guide');
        $blog->set('title', 'A PHP handbook for teams');

        $pageCollection = $this->collection($page, $blog)->search('PHP guide', minimumLength: 3, weights: [
            'title'   => 100,
            'summary' => 0,
            'content' => 0,
            'author'  => 0,
            'uri'     => 0,
        ]);

        $this->assertNotEmpty($pageCollection);
        $this->assertContains($page, $pageCollection);
        $this->assertContains($blog, $pageCollection);

        $this->assertSame([$page, $blog], $pageCollection->values());
    }

    public function testSearchExcludesPagesWithoutMatches(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $page->set('title', 'PHP guide');
        $blog->set('title', 'Cooking recipes');

        $pageCollection = $this->collection($page, $blog)->search('PHP guide', minimumLength: 3, weights: [
            'title'   => 100,
            'summary' => 0,
            'content' => 0,
            'author'  => 0,
            'uri'     => 0,
        ]);

        $this->assertSame([$page], $pageCollection->values());
    }

    public function testSearchStoresTheScoresOfTheMatchingPages(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $other = $this->page('/index');
        $page->set('title', 'PHP guide');
        $blog->set('title', 'A PHP handbook for teams');
        $other->set('title', 'Cooking recipes');

        $pageCollection = $this->collection($page, $blog, $other)->search('PHP guide', minimumLength: 3, weights: [
            'title'   => 100,
            'summary' => 0,
            'content' => 0,
            'author'  => 0,
            'uri'     => 0,
        ], scores: $scores);

        /** @var array<string, int> $scores */
        $this->assertSame($pageCollection->keys(), array_keys($scores));
        $this->assertCount(2, $scores);
        $this->assertGreaterThan($scores[$pageCollection->keys()[1]], $scores[$pageCollection->keys()[0]]);
        $this->assertContainsOnlyInt($scores);
    }

    #[DataProvider('searchWithoutResultsProvider')]
    public function testSearchScoresAreEmptyWhenThereAreNoResults(string $query): void
    {
        $page = $this->page('/about');
        $page->set('title', 'PHP guide');

        $scores = ['stale' => 1];
        $pageCollection = $this->collection($page)->search($query, scores: $scores);

        $this->assertTrue($pageCollection->isEmpty());
        $this->assertSame([], $scores);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function searchWithoutResultsProvider(): iterable
    {
        yield 'query shorter than the minimum length' => ['php'];
        yield 'no keyword reaches the minimum length' => ['a b c d'];
        yield 'no page matches' => ['unrelated words'];
    }

    public function testSearchDoesNotAddTransientScoresToTheSourcePages(): void
    {
        $page = $this->page('/about');
        $page->set('title', 'PHP guide');
        $this->assertFalse($page->has('score'));

        $this->collection($page)->search('PHP guide');

        $this->assertFalse($page->has('score'));
    }

    public function testSearchPreservesResultOrderAfterRepeatedExecution(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $page->set('title', 'PHP guide');
        $blog->set('title', 'PHP');
        $collection = $this->collection($page, $blog);

        $pageCollection = $collection->search('PHP', minimumLength: 3);
        $second = $collection->search('PHP', minimumLength: 3);

        $this->assertSame($pageCollection->values(), $second->values());
        $this->assertSame([$page, $blog], $pageCollection->values());
    }

    public function testPageRelationshipExclusionMethodsRemoveExpectedPages(): void
    {
        $page = $this->page('/blog');
        $child = $this->page('/blog/hello-world');
        $about = $this->page('/about');
        $collection = $this->collection($page, $child, $about);

        $this->assertSame([$page, $about], $collection->withoutChildren($page)->values());
        $this->assertSame([$about], $collection->withoutPageAndChildren($page)->values());
        $this->assertSame([$page, $about], $collection->withoutDescendants($page)->values());
        $this->assertSame([$about], $collection->withoutPageAndDescendants($page)->values());
        $this->assertSame([$child, $about], $collection->withoutParent($child)->values());
        $this->assertSame([$about], $collection->withoutPageAndParent($child)->values());
        $this->assertSame([$child, $about], $collection->withoutSiblings($about)->values());
    }

    public function testPaginationDoesNotMutateTheSourceCollection(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $collection = $this->collection($page, $blog);
        $before = $collection->values();

        $pageCollection = $collection->paginate(1, 2);

        $this->assertSame([$blog], $pageCollection->values());
        $this->assertSame($before, $collection->values());
    }

    public function testSortByReturnsANewSortedPageCollectionAndLeavesTheOriginalUntouched(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $collection = $this->collection($blog, $page);
        $before = $collection->values();

        $pageCollection = $collection->sortBy('title');
        $descending = $collection->sortBy('title', SORT_DESC);

        $this->assertInstanceOf(PageCollection::class, $pageCollection);
        $this->assertSame([$page, $blog], $pageCollection->values());
        $this->assertSame([$blog, $page], $descending->values());
        $this->assertSame($before, $collection->values());
    }

    public function testRelationshipFiltersPreservePageIdentityAndSourceCollection(): void
    {
        $page = $this->page('/blog');
        $child = $this->page('/blog/hello-world');
        $about = $this->page('/about');
        $collection = $this->collection($page, $child, $about);
        $before = $collection->values();

        $pageCollection = $collection->withoutPageAndDescendants($page);

        $this->assertSame([$about], $pageCollection->values());
        $this->assertSame($before, $collection->values());
        $this->assertSame($page, $collection->at(0));
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
}
