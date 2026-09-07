<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageBeforeSaveEvent extends Event
{
    public function __construct(Page $page)
    {
        parent::__construct('pageBeforeSave', ['page' => $page]);
    }

    /**
     * Get the page being saved
     */
    public function page(): Page
    {
        return $this->data['page'];
    }
}
