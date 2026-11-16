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
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use UnexpectedValueException;

#[CoversClass(Pagination::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PaginationTest extends TestCase
{
    private Site $fixtureSite;

    protected function setUp(): void
    {
        parent::setUp();
        $app = App::instance();
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'pagination-site');
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/site', $path);
        $this->fixtureSite = new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => []],
            $app->config(),
            $app->getService(PageFactory::class),
            $app->getService(PageCollectionFactory::class),
        );
    }

    public function testPaginationRoutesAndUrisAreGeneratedForAllNavigationLinks(): void
    {
        $pages = $this->fixtureSite->pages();
        $router = $this->createStub(Router::class);
        $base = new Route('page', '/{page:all}/');
        $pagination = new Route('page.pagination', '/{page:all}/page/{paginationPage:number}/');
        $routeCollection = new RouteCollection([$base->getName() => $base, $pagination->getName() => $pagination]);

        $router->method('current')->willReturn($pagination);
        $router->method('routes')->willReturn($routeCollection);
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
        $this->assertSame($result->uri(1), $result->firstPageUri());
        $this->assertSame($result->uri($result->lastPage()), $result->lastPageUri());
        $this->assertSame($result->uri($result->previousPage()), $result->previousPageUri());
        $this->assertSame($result->uri($result->nextPage()), $result->nextPageUri());
    }

    /**
     * @param list<string> $routes
     */
    #[DataProvider('missingRouteProvider')]
    public function testRoutesCannotBeGeneratedWithoutTheirRoutes(?string $current, array $routes, string $message): void
    {
        $router = $this->createStub(Router::class);
        $router->method('current')->willReturn($current === null ? null : new Route($current, '/'));
        $router->method('routes')->willReturn(new RouteCollection(array_combine($routes, array_map(static fn(string $name): Route => new Route($name, '/'), $routes))));

        $pagination = new Pagination($this->fixtureSite->pages(), 1, $this->fixtureSite, $router);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $pagination->route(2);
    }

    /**
     * @return iterable<string, array{?string, list<string>, string}>
     */
    public static function missingRouteProvider(): iterable
    {
        yield 'current route' => [null, [], 'Cannot generate pagination routes, current route is not defined'];
        yield 'base route' => ['page.pagination', [], 'Cannot generate pagination routes, base route "page" is not defined'];
        yield 'pagination route' => ['page', ['page'], 'Cannot generate pagination for route "page", route "page.pagination" is not defined'];
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
