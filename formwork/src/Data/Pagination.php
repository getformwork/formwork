<?php

namespace Formwork\Data;

class Pagination
{
    /**
     * Number of all items to paginate
     */
    protected int $count = 0;

    /**
     * Number of items in each pagination page
     */
    protected int $length = 0;

    /**
     * Number of pagination pages
     */
    protected int $pages = 0;

    /**
     * Current pagination page
     */
    protected int $currentPage = 1;

    /**
     * Create a new Pagination instance
     */
    public function __construct(AbstractCollection $collection, int $length)
    {
        $this->count = $collection->count();

        $this->length = $length;

        $this->pages = $this->count > 0 ? ceil($this->count / $this->length) : 1;
    }

    /**
     * Get current page
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    /**
     * Get pagination length
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * Get current pagination offset
     */
    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->length;
    }

    public function pages(): int
    {
        return $this->pages;
    }

    /**
     * Return whether pagination has more than one page
     */
    public function hasPages(): bool
    {
        return $this->pages > 1;
    }

    /**
     * Return whether a given page number exists
     */
    public function has(int $pageNumber): bool
    {
        return $pageNumber >= 1 && $pageNumber <= $this->pages;
    }

    /**
     * Return whether current page is the first
     */
    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    public function firstPage(): int
    {
        return 1;
    }

    /**
     * Return whether current page is the last
     */
    public function isLastPage(): bool
    {
        return $this->currentPage === $this->pages;
    }

    public function lastPage(): int
    {
        return $this->pages;
    }

    /**
     * Return whether a previous page exists
     */
    public function hasPreviousPage(): bool
    {
        return !$this->isFirstPage();
    }

    /**
     * Get previous pagination page number
     *
     * @return bool|int
     */
    public function previousPage(): int
    {
        return ($previous = $this->currentPage - 1) > 0
            ? $previous
            : $this->firstPage();
    }

    /**
     * Return whether a next page exists
     */
    public function hasNextPage(): bool
    {
        return !$this->isLastPage();
    }

    /**
     * Get next pagination page number
     */
    public function nextPage(): int
    {
        return ($next = $this->currentPage + 1) <= $this->pages
            ? $next
            : $this->lastPage();
    }
}
