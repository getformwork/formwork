<?php

namespace Formwork\Data\Contracts;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Pagination;

/**
 * @template T
 */
interface Paginable
{
    /**
     * Return the pagination for the collection
     *
     * @return Pagination<T>
     */
    public function pagination(): Pagination;

    /**
     * Paginate the collection
     *
     * @return AbstractCollection<T>
     */
    public function paginate(int $length, int $currentPage): AbstractCollection;
}
