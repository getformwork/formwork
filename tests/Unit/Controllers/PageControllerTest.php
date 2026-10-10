<?php

namespace Formwork\Tests\Unit\Controllers;

use Formwork\Cache\AbstractCache;
use Formwork\Cache\ArrayCache;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Controllers\PageController;
use Formwork\Events\EventDispatcher;
use Formwork\Http\FileResponse;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\Events\PageOutputEvent;
use Formwork\Router\RouteParams;
use Formwork\Router\Router;
use Formwork\Statistics\Statistics;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Controllers\Fixtures\BuildsControllers;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use Formwork\Translations\Translation;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(PageController::class)]
final class PageControllerTest extends TestCase
{
    use BuildsControllers;
    use BuildsPageSites;

    private Site $site;

    /**
     * @var array<string, mixed>
     */
    private array $siteSettings = [];

    private ArrayCache $cache;

    private Statistics $statistics;

    private string $statisticsPath;

    private int $renderedPages = 0;

    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

        $this->site = $this->siteFromFiles([
            'index/index.md'    => "---\ntitle: Home\n---\nHome content\n",
            'about/page.md'     => "---\ntitle: About us\n---\nAbout content\n",
            'about/diagram.svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>',
            'draft/page.md'     => "---\ntitle: Draft\npublished: false\n---\nDraft content\n",
            'canonical/page.md' => "---\ntitle: Canonical\ncanonicalRoute: /about/\n---\nCanonical content\n",
            'blog/blog.md'      => "---\ntitle: Blog\n---\nBlog content\n",
            'error/error.md'    => "---\ntitle: Not found\n---\nThis page does not exist\n",
        ]);

        $this->cache = new ArrayCache('pages');
        $this->statisticsPath = TESTS_TMP_PATH . '/statistics';

        $request = new Request([], [], [], [], ['REMOTE_ADDR' => '203.0.113.7', 'REQUEST_METHOD' => 'GET', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) Firefox/120.0']);
        $options = App::instance()->config()->getArray('site.statistics');
        $this->statistics = new Statistics([...$options, 'path' => $this->statisticsPath], $request, new Translation('en', []));

        $this->renderedPages = 0;
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->on('pageOutput', function (PageOutputEvent $event): void {
            ++$this->renderedPages;
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testPublishedPagesAreRendered(): void
    {
        $response = $this->load('/about/');

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertStringContainsString('About us', $response->content());
        $this->assertStringContainsString('<!DOCTYPE html>', $response->content());
    }

    public function testTheIndexPageIsUsedWhenNoPageIsRequested(): void
    {
        $response = $this->load(null);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertNotSame($this->load('/about/')->content(), $response->content());
    }

    public function testUnpublishedPagesAreNotRevealed(): void
    {
        $response = $this->load('/draft/');

        $this->assertSame(ResponseStatus::NotFound, $response->status());
        $this->assertStringNotContainsString('Draft content', $response->content());
    }

    public function testUnknownRoutesRenderTheErrorPage(): void
    {
        $response = $this->load('/does-not-exist/');

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testErrorActionRendersTheErrorPage(): void
    {
        $response = $this->controller()->error();

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testFilesOfAPageAreServedByTheirRoute(): void
    {
        $response = $this->load('/about/diagram.svg');

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame($this->site->contentPath() . 'about/diagram.svg', $this->fileOf($response));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fileTraversals(): iterable
    {
        yield 'parent segment' => ['/about/../draft/page.md'];
        yield 'deep parents' => ['/about/../../../../../../../etc/hostname'];
        yield 'parent of the content' => ['/../composer.json'];
        yield 'backslashes' => ['/about/..\draft\page.md'];
        yield 'page content files' => ['/about/page.md'];
    }

    #[DataProvider('fileTraversals')]
    public function testFilesOutsideThePageDirectoryAreNeverServed(string $route): void
    {
        $response = $this->load($route);

        $this->assertNotInstanceOf(FileResponse::class, $response, 'Served ' . ($response instanceof FileResponse ? $this->fileOf($response) : ''));
    }

    public function testPagesWithACanonicalRouteAreRedirected(): void
    {
        $response = $this->load('/canonical/');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(ResponseStatus::MovedPermanently, $response->status());
        $this->assertStringEndsWith('/about/', (string) $response->headers()->get('Location'));
    }

    public function testPaginationIsRefusedForPagesThatDoNotAllowIt(): void
    {
        $response = $this->load('/about/', ['paginationPage' => '2']);

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testTaxonomyRoutesAreRefusedForPagesThatDoNotAllowThem(): void
    {
        $response = $this->load('/about/', ['taxonomy' => 'tags', 'taxonomyTerm' => 'php']);

        $this->assertSame(ResponseStatus::NotFound, $response->status());
    }

    public function testPaginationIsAcceptedForPagesThatAllowIt(): void
    {
        $response = $this->load('/blog/', ['paginationPage' => '1']);

        $this->assertSame(ResponseStatus::OK, $response->status());
    }

    // Maintenance

    public function testMaintenanceModeServesTheMaintenancePage(): void
    {
        $this->siteSettings['maintenance.enabled'] = true;

        $response = $this->load('/about/');

        $this->assertSame(ResponseStatus::ServiceUnavailable, $response->status());
        $this->assertStringNotContainsString('About content', $response->content());
    }

    // Statistics

    public function testVisitsToPublishedPagesAreTracked(): void
    {
        $this->load('/about/');

        $this->assertFileExists($this->statisticsPath . '/visits.json');
    }

    public function testVisitsAreNotTrackedWhenStatisticsAreDisabled(): void
    {
        $this->load('/about/', site: ['statistics' => ['enabled' => false]]);

        $this->assertFileDoesNotExist($this->statisticsPath . '/visits.json');
    }

    public function testVisitsToErrorPagesAreNotTracked(): void
    {
        $this->load('/does-not-exist/');
        $this->load('/draft/');

        $this->assertFileDoesNotExist($this->statisticsPath . '/visits.json');
    }

    public function testVisitsDuringMaintenanceAreNotTracked(): void
    {
        $this->siteSettings['maintenance.enabled'] = true;

        $this->load('/about/');

        $this->assertFileDoesNotExist($this->statisticsPath . '/visits.json');
    }

    // Cache

    public function testCachedPagesAreNotRenderedAgain(): void
    {
        $first = $this->load('/about/', system: ['cache' => ['enabled' => true]]);
        $second = $this->load('/about/', system: ['cache' => ['enabled' => true]]);

        $this->assertSame($first->content(), $second->content());
        $this->assertSame(1, $this->renderedPages);
    }

    public function testCachingIsOffByDefault(): void
    {
        $this->load('/about/', system: ['cache' => ['enabled' => false]]);
        $this->load('/about/', system: ['cache' => ['enabled' => false]]);

        $this->assertSame(2, $this->renderedPages);
        $this->assertSame(0, $this->cache->count());
    }

    public function testCachedPagesCarryValidators(): void
    {
        $response = $this->load('/about/', system: ['cache' => ['enabled' => true]]);

        $this->assertNotNull($response->headers()->get('ETag'));
        $this->assertNotNull($response->headers()->get('Last-Modified'));
    }

    public function testRequestsWithAQueryStringAreNotCached(): void
    {
        $this->load('/about/', system: ['cache' => ['enabled' => true]], query: ['utm_source' => 'newsletter']);
        $this->load('/about/', system: ['cache' => ['enabled' => true]], query: ['utm_source' => 'newsletter']);

        $this->assertSame(2, $this->renderedPages);
    }

    public function testPostRequestsAreNotCached(): void
    {
        $this->load('/about/', system: ['cache' => ['enabled' => true]], server: ['REQUEST_METHOD' => 'POST']);
        $this->load('/about/', system: ['cache' => ['enabled' => true]], server: ['REQUEST_METHOD' => 'POST']);

        $this->assertSame(2, $this->renderedPages);
    }

    public function testErrorPagesAreNotCached(): void
    {
        $this->load('/does-not-exist/', system: ['cache' => ['enabled' => true]]);
        $this->load('/does-not-exist/', system: ['cache' => ['enabled' => true]]);

        $this->assertSame(0, $this->cache->count());
    }

    public function testDifferentRoutesUseDifferentCacheEntries(): void
    {
        $about = $this->load('/about/', system: ['cache' => ['enabled' => true]]);
        $home = $this->load('/', system: ['cache' => ['enabled' => true]]);

        $this->assertNotSame($about->content(), $home->content());
        $this->assertSame(2, $this->cache->count());
    }

    public function testCachedPagesAreInvalidatedWhenTheContentChanges(): void
    {
        $this->load('/about/', system: ['cache' => ['enabled' => true]]);
        $this->assertSame(1, $this->renderedPages);

        $content = (string) $this->site->contentPath();
        FileSystem::write($content . 'about/page.md', "---\ntitle: About us\n---\nUpdated content\n");
        touch($content . 'about/page.md', time() + 10);
        touch($content, time() + 10);

        $response = $this->load('/about/', system: ['cache' => ['enabled' => true]]);

        $this->assertSame(2, $this->renderedPages);
        $this->assertStringContainsString('Updated content', $response->content());
    }

    public function testCorruptedCacheEntriesAreDiscarded(): void
    {
        $this->cache->set(rawurlencode('/about/'), 'not a response');

        $response = $this->load('/about/', system: ['cache' => ['enabled' => true]]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('About us', $response->content());
    }

    /**
     * @param array<string, mixed> $system
     * @param array<string, mixed> $site
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     */
    private function load(?string $route, array $parameters = [], array $system = [], array $site = [], array $query = [], array $server = []): Response
    {
        if (array_is_list($parameters) === false && $route === null) {
            $parameters = [];
        }

        $routeParameters = $route === null ? $parameters : ['page' => $route] + $parameters;

        return $this->controller($route ?? '/', $system, $site, $query, $server)->load(new RouteParams($routeParameters), $this->statistics);
    }

    /**
     * @param array<string, mixed> $system
     * @param array<string, mixed> $site
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     */
    private function controller(string $requested = '/', array $system = [], array $site = [], array $query = [], array $server = []): PageController
    {
        // The current page is fixed once per request, so every controller gets its own site
        $freshSite = $this->siteFromPath((string) $this->site->contentPath());
        foreach ($this->siteSettings as $key => $value) {
            $freshSite->set($key, $value);
        }

        $router = $this->createStub(Router::class);
        $router->method('request')->willReturn($requested);
        $router->method('rewrite')->willReturnCallback(static fn(array $params): string => (string) ($params['page'] ?? '/'));
        $router->method('current')->willReturn(null);

        return $this->makeController(
            PageController::class,
            $system,
            $query,
            $server,
            [
                Site::class            => $freshSite,
                'cache.pages'          => $this->cache,
                AbstractCache::class   => $this->cache,
                EventDispatcher::class => $this->dispatcher,
                ViewFactory::class     => App::instance()->getService(ViewFactory::class),
            ],
            $router,
            $site,
        );
    }

    private function fileOf(Response $response): string
    {
        return (string) (new ReflectionProperty($response, 'path'))->getValue($response);
    }
}
