<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageLoadedEvent extends Event
{
    public function __construct(Page $page)
    {
        parent::__construct('pageLoaded', ['page' => $page]);
    }

    /**
     * Get the loaded page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }
}
