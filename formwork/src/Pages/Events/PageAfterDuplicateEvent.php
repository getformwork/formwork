<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageAfterDuplicateEvent extends Event
{
    public function __construct(Page $page, Page $duplicatePage)
    {
        parent::__construct('pageAfterDuplicate', ['page' => $page, 'duplicatePage' => $duplicatePage]);
    }

    /**
     * Get the loaded page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }

    /**
     * Get the duplicated page
     */
    public function duplicatePage(): Page
    {
        return $this->data['duplicatePage'];
    }
}
