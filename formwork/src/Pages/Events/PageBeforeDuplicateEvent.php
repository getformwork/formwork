<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageBeforeDuplicateEvent extends Event
{
    /**
     * @param array<string, mixed> $with
     */
    public function __construct(Page $page, array &$with)
    {
        parent::__construct('pageBeforeDuplicate', ['page' => $page, 'with' => &$with]);
    }

    /**
     * Get the page being duplicated
     */
    public function page(): Page
    {
        return $this->data['page'];
    }

    /**
     * Get the data to duplicate the page with
     *
     * @return array<string, mixed>
     */
    public function &with(): array
    {
        return $this->data['with'];
    }
}
