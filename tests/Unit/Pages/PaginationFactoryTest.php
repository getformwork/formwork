<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\Pagination;
use Formwork\Pages\PaginationFactory;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PaginationFactory::class)]
#[UsesApplication]
final class PaginationFactoryTest extends TestCase
{
    public function testPaginationIsBuiltForTheCollectionAndLength(): void
    {
        $pageCollection = App::instance()->getService(PageCollectionFactory::class)->make([]);
        $paginationFactory = new PaginationFactory(App::instance(), $this->createStub(Router::class));

        $pagination = $paginationFactory->make($pageCollection, 5);

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame(5, $pagination->length());
        $this->assertSame(1, $pagination->pages(), 'An empty collection still has a single page');
    }

    public function testEachCallBuildsAnIndependentPagination(): void
    {
        $collection = $this->createStub(PageCollection::class);
        $paginationFactory = new PaginationFactory(App::instance(), $this->createStub(Router::class));

        $this->assertNotSame($paginationFactory->make($collection, 3), $paginationFactory->make($collection, 3));
    }
}
