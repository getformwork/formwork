<?php

namespace Formwork\Tests\Unit\Pages;

use Formwork\Cms\App;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\Pagination;
use Formwork\Pages\PaginationFactory;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PaginationFactory::class)]
final class PaginationFactoryTest extends TestCase
{
    public function testPaginationIsBuiltForTheCollectionAndLength(): void
    {
        $collection = App::instance()->getService(PageCollectionFactory::class)->make([]);
        $factory = new PaginationFactory(App::instance(), $this->createStub(Router::class));

        $pagination = $factory->make($collection, 5);

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame(5, $pagination->length());
        $this->assertSame(1, $pagination->pages(), 'An empty collection still has a single page');
    }

    public function testEachCallBuildsAnIndependentPagination(): void
    {
        $collection = $this->createStub(PageCollection::class);
        $factory = new PaginationFactory(App::instance(), $this->createStub(Router::class));

        $this->assertNotSame($factory->make($collection, 3), $factory->make($collection, 3));
    }
}
