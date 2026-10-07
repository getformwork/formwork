<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Pages\Pagination;
use Formwork\Router\Route;
use Formwork\Router\RouteCollection;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    private App $app;

    private Site $fixtureSite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->app = App::instance();
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'pagination-site');
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/site', $path);
        $this->fixtureSite = new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testPaginationRoutesAndUrisAreGeneratedForAllNavigationLinks(): void
    {
        $pages = $this->fixtureSite->pages();
        $router = $this->createStub(Router::class);
        $base = new Route('page', '/{page:all}/');
        $pagination = new Route('page.pagination', '/{page:all}/page/{paginationPage:number}/');
        $routes = new RouteCollection([$base->getName() => $base, $pagination->getName() => $pagination]);

        $router->method('current')->willReturn($pagination);
        $router->method('routes')->willReturn($routes);
        $router->method('generateWith')->willReturnCallback(static function (string $name, array $parameters): string {
            return $name === 'page' ? '/about/' : '/about/page/' . $parameters['paginationPage'] . '/';
        });

        $result = new Pagination($pages, 1, $this->fixtureSite, $router);
        $this->assertSame('/about/', $result->route(1));
        $this->assertSame('/about/page/2/', $result->route(2));
        $this->assertStringEndsWith('/about/page/2/', $result->uri(2));
        $this->assertSame($result->route(1), $result->firstPageRoute());
        $this->assertSame($result->route($result->lastPage()), $result->lastPageRoute());
        $this->assertSame($result->route($result->previousPage()), $result->previousPageRoute());
        $this->assertSame($result->route($result->nextPage()), $result->nextPageRoute());
    }

    public function testRouteRejectsPageNumbersOutsideThePagination(): void
    {
        $router = $this->createStub(Router::class);
        $router->method('current')->willReturn(new Route('page', '/'));

        $pagination = new Pagination($this->fixtureSite->pages(), 100, $this->fixtureSite, $router);

        $this->expectException(UnexpectedValueException::class);
        $pagination->route(2);
    }
}
