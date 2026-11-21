<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Tests\TestCase;
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

    private const array TITLE_ONLY_WEIGHTS = ['title' => 100, 'summary' => 0, 'content' => 0, 'author' => 0, 'uri' => 0];

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

    /**
     * @param list<string> $expectedRoutes
     */
    #[DataProvider('outOfRangePaginationProvider')]
    public function testPaginationClampsTheCurrentPageToTheExistingOnes(int $length, int $currentPage, int $expectedPage, array $expectedRoutes): void
    {
        $pageCollection = $this->collection($this->page('/about'), $this->page('/blog'))->paginate($length, $currentPage);

        $this->assertSame($expectedPage, $pageCollection->pagination()->currentPage());
        $this->assertSame($expectedRoutes, $pageCollection->keys());
    }

    /**
     * @return iterable<string, array{int, int, int, list<string>}>
     */
    public static function outOfRangePaginationProvider(): iterable
    {
        yield 'page before the first' => [1, 0, 1, ['/about/']];
        yield 'page after the last' => [1, 5, 2, ['/blog/']];
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

        $pageCollection = $this->collection($page, $blog)->search('PHP guide', minimumLength: 3, weights: self::TITLE_ONLY_WEIGHTS);

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

        $pageCollection = $this->collection($page, $blog)->search('PHP guide', minimumLength: 3, weights: self::TITLE_ONLY_WEIGHTS);

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

        $pageCollection = $this->collection($page, $blog, $other)->search('PHP guide', minimumLength: 3, weights: self::TITLE_ONLY_WEIGHTS, scores: $scores);

        /** @var array<string, int> $scores */
        $this->assertSame($pageCollection->keys(), array_keys($scores));
        $this->assertCount(2, $scores);
        $this->assertGreaterThan($scores[$pageCollection->keys()[1]], $scores[$pageCollection->keys()[0]]);
        $this->assertContainsOnlyInt($scores);
    }

    public function testSearchScoresCountWholeQueryMatchesTwiceAndKeywordMatchesUpToTheLimit(): void
    {
        $page = $this->page('/about');
        $blog = $this->page('/blog');
        $other = $this->page('/index');
        $page->set('title', 'PHP guide');
        $blog->set('title', 'A PHP handbook for teams');
        $other->set('title', 'PHP PHP PHP PHP PHP');

        $results = $this->collection($page, $blog, $other)->search('php GUIDE', minimumLength: 3, weights: self::TITLE_ONLY_WEIGHTS, scores: $scores);

        // The whole query counts twice, each keyword once and at most three times per field
        $this->assertSame([$page, $other, $blog], $results->values());
        $this->assertSame([$this->routeOf($page) => 400, $this->routeOf($blog) => 100, $this->routeOf($other) => 300], $scores);
    }

    public function testSearchIgnoresKeywordsShorterThanTheMinimumLength(): void
    {
        $page = $this->page('/about');
        $page->set('title', 'a zebra');

        $this->collection($page)->search('zebra a', weights: self::TITLE_ONLY_WEIGHTS, scores: $scores);

        $this->assertSame([$this->routeOf($page) => 100], $scores);
    }

    #[DataProvider('defaultSearchWeightProvider')]
    public function testSearchWeighsTheFieldsByDefault(string $field, int $weight): void
    {
        $page = $this->page('/about');
        $page->set('title', 'Untitled');
        $page->set($field, 'zebra');

        $this->collection($page)->search('zebra', scores: $scores);

        $this->assertSame([$this->routeOf($page) => 3 * $weight], $scores);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function defaultSearchWeightProvider(): iterable
    {
        yield 'title' => ['title', 8];
        yield 'summary' => ['summary', 4];
        yield 'content' => ['content', 3];
        yield 'author' => ['author', 2];
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
        $this->assertSame([$page, $about], $collection->withoutPageAndSiblings($child)->values());
        $this->assertSame([$child, $about], $collection->withoutAncestors($child)->values());
        $this->assertSame([$about], $collection->withoutPageAndAncestors($child)->values());
    }

    public function testExcludingTheParentOfAPageWithoutParentExcludesNothingElse(): void
    {
        $page = new Page(['site' => $this->fixtureSite], $this->app);
        $about = $this->page('/about');
        $collection = $this->collection($about);

        $this->assertSame([$about], $collection->withoutParent($page)->values());
        $this->assertSame([$about], $collection->withoutPageAndParent($page)->values());
    }

    public function testPagesWithTheSameTitleOfARelatedPageAreNotRemoved(): void
    {
        $page = $this->page('/blog');
        $child = $this->page('/blog/hello-world');
        $about = $this->page('/about');
        // Pages are converted to strings by their title
        $about->set('title', (string) $child->title());
        $collection = $this->collection($page, $child, $about);

        $this->assertSame([$page, $about], $collection->withoutChildren($page)->values());
        $this->assertSame([$page, $about], $collection->withoutDescendants($page)->values());
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

    private function routeOf(Page $page): string
    {
        return $page->route() ?? $this->fail('Missing page route');
    }

    private function collection(Page ...$pages): PageCollection
    {
        $data = [];
        foreach ($pages as $page) {
            $data[$this->routeOf($page)] = $page;
        }

        return $this->app->getService(PageCollectionFactory::class)->make($data);
    }
}
