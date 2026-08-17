<?php

namespace Formwork\Pages;

use Formwork\Cms\Site;

final class PageCollectionFactory
{
    public function __construct(
        private PaginationFactory $paginationFactory,
    ) {}

    /**
     * Create a new PageCollection instance
     *
     * @param array<int|string, Page|Site> $data
     */
    public function make(array $data): PageCollection
    {
        return new PageCollection($data, $this->paginationFactory);
    }
}
