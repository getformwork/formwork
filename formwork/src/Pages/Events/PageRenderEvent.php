<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

/**
 * @since 2.3.0
 */
class PageRenderEvent extends Event
{
    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(Page $page, array &$vars)
    {
        parent::__construct('pageRender', ['page' => $page, 'vars' => &$vars]);
    }

    /**
     * Get the rendered page
     */
    public function page(): Page
    {
        return $this->data['page'];
    }

    /**
     * Get the rendering variables
     *
     * @return array<string, mixed>
     */
    public function &vars(): array
    {
        return $this->data['vars'];
    }
}
