<?php

namespace Formwork\Data\Contracts;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Pagination;

interface Paginable
{
    public function pagination(): Pagination;

    public function paginate(int $length, int $currentPage): AbstractCollection;
}
