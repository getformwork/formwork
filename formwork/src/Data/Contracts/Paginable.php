<?php

namespace Formwork\Data\Contracts;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Pagination;

interface Paginable
{
    /**
     * Return the pagination for the collection
     */
    public function pagination(): Pagination;

    /**
     * Paginate the collection
     */
    public function paginate(int $length, int $currentPage): AbstractCollection;
}
