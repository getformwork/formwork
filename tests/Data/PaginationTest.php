<?php

namespace Formwork\Tests\Data;

use Formwork\Data\Collection;
use Formwork\Data\Pagination;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function testPaginationProperties(): void
    {
        $collection = Collection::from(range(1, 50));
        $pagination = new Pagination($collection, 10);

        $this->assertSame(10, $pagination->length());
        $this->assertSame(5, $pagination->pages());
        $this->assertSame($pagination->pages(), $pagination->lastPage());
        $this->assertSame(1, $pagination->currentPage());
        $this->assertSame(0, $pagination->offset());
        $this->assertTrue($pagination->hasPages());

        $this->assertTrue($pagination->has(1));
        $this->assertTrue($pagination->has(5));
        $this->assertFalse($pagination->has(6));
        $this->assertFalse($pagination->has(0));

        $this->assertTrue($pagination->isFirstPage());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertFalse($pagination->isLastPage());
        $this->assertFalse($pagination->hasPreviousPage());
        $this->assertSame(1, $pagination->previousPage());
        $this->assertSame(2, $pagination->nextPage());

        $pagination->setCurrentPage(3);
        $this->assertSame(3, $pagination->currentPage());
        $this->assertSame(20, $pagination->offset());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertSame(2, $pagination->previousPage());
        $this->assertSame(4, $pagination->nextPage());

        $pagination->setCurrentPage(5);
        $this->assertSame(5, $pagination->currentPage());
        $this->assertSame(40, $pagination->offset());
        $this->assertFalse($pagination->hasNextPage());
        $this->assertTrue($pagination->isLastPage());
        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertSame(4, $pagination->previousPage());
        $this->assertSame(5, $pagination->nextPage());
    }
}
