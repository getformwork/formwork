<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageOutputEvent extends Event
{
    public function __construct(Page $page, string &$output)
    {
        parent::__construct('pageOutput', ['page' => $page, 'output' => &$output]);
    }

    /**
     * Get the rendered page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }

    /**
     * Get the output
     */
    public function &output(): string
    {
        return $this->data['output'];
    }
}
