<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageAfterSaveEvent extends Event
{
    public function __construct(Page $page)
    {
        parent::__construct('pageAfterSave', ['page' => $page]);
    }

    /**
     * Get the saved page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }
}
