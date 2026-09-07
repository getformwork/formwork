<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageAfterDeleteEvent extends Event
{
    public function __construct(Page $page)
    {
        parent::__construct('pageAfterDelete', ['page' => $page]);
    }

    /**
     * Get the deleted page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }
}
