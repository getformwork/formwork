<?php

namespace Formwork\Tests\Unit\Data;

use Formwork\Data\Collection;
use Formwork\Data\Pagination;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public function testFirstPageProperties(): void
    {
        $pagination = new Pagination(Collection::from(range(1, 50)), 10);

        $this->assertSame(10, $pagination->length());
        $this->assertSame(5, $pagination->pages());
        $this->assertSame($pagination->pages(), $pagination->lastPage());
        $this->assertSame(1, $pagination->firstPage());
        $this->assertSame(1, $pagination->currentPage());
        $this->assertSame(0, $pagination->offset());
        $this->assertTrue($pagination->hasPages());
        $this->assertTrue($pagination->isFirstPage());
        $this->assertFalse($pagination->isLastPage());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertFalse($pagination->hasPreviousPage());
        $this->assertSame(1, $pagination->previousPage());
        $this->assertSame(2, $pagination->nextPage());
    }

    public function testMiddlePageProperties(): void
    {
        $pagination = new Pagination(Collection::from(range(1, 50)), 10);

        $pagination->setCurrentPage(3);

        $this->assertSame(3, $pagination->currentPage());
        $this->assertSame(20, $pagination->offset());
        $this->assertFalse($pagination->isFirstPage());
        $this->assertFalse($pagination->isLastPage());
        $this->assertTrue($pagination->hasNextPage());
        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertSame(2, $pagination->previousPage());
        $this->assertSame(4, $pagination->nextPage());
    }

    public function testLastPageProperties(): void
    {
        $pagination = new Pagination(Collection::from(range(1, 50)), 10);

        $pagination->setCurrentPage(5);

        $this->assertSame(40, $pagination->offset());
        $this->assertFalse($pagination->isFirstPage());
        $this->assertTrue($pagination->isLastPage());
        $this->assertFalse($pagination->hasNextPage());
        $this->assertTrue($pagination->hasPreviousPage());
        $this->assertSame(4, $pagination->previousPage());
        $this->assertSame(5, $pagination->nextPage());
    }

    #[DataProvider('pageCountProvider')]
    public function testNumberOfPagesIsTheCeilingOfItemsOverLength(int $items, int $length, int $expectedPages): void
    {
        $pagination = new Pagination(Collection::from($items > 0 ? range(1, $items) : []), $length);

        $this->assertSame($expectedPages, $pagination->pages());
        $this->assertSame($expectedPages > 1, $pagination->hasPages());
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function pageCountProvider(): iterable
    {
        yield 'exact fit' => [50, 10, 5];
        yield 'one item over' => [51, 10, 6];
        yield 'one item short' => [49, 10, 5];
        yield 'fewer items than the length' => [3, 10, 1];
        yield 'exactly one page' => [10, 10, 1];
        yield 'one item per page' => [5, 1, 5];
        yield 'empty collection' => [0, 10, 1];
    }

    public function testHasTellsWhetherAPageExists(): void
    {
        $pagination = new Pagination(Collection::from(range(1, 25)), 10);

        $this->assertFalse($pagination->has(0));
        $this->assertFalse($pagination->has(-1));
        $this->assertTrue($pagination->has(1));
        $this->assertTrue($pagination->has(3));
        $this->assertFalse($pagination->has(4));
    }

    public function testNavigationStaysInsideTheExistingPages(): void
    {
        $pagination = new Pagination(Collection::from([1]), 10);

        $this->assertTrue($pagination->isFirstPage());
        $this->assertTrue($pagination->isLastPage());
        $this->assertSame(1, $pagination->previousPage());
        $this->assertSame(1, $pagination->nextPage());
    }

    #[DataProvider('invalidCurrentPageProvider')]
    public function testOffsetIsNeverNegative(int $currentPage): void
    {
        $pagination = new Pagination(Collection::from(range(1, 50)), 10);

        $rejected = false;

        try {
            $pagination->setCurrentPage($currentPage);
        } catch (InvalidArgumentException) {
            $rejected = true;
        }

        $this->assertTrue($rejected || $pagination->offset() >= 0, 'A non-positive page must be rejected or clamped to a valid offset');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidCurrentPageProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-3];
    }

    #[DataProvider('invalidLengthProvider')]
    public function testNonPositiveLengthsAreRejected(int $length): void
    {
        $this->expectException(Throwable::class);
        new Pagination(Collection::from(range(1, 5)), $length);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidLengthProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-5];
    }
}
