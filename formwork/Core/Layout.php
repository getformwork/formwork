<?php

namespace Formwork\Core;

class Layout extends Template
{
    /**
     * Layout content
     *
     * @var string
     */
    protected $content;

    /**
     * Create a new Layout instance
     *
     * @param string $layout
     */
    public function __construct($layout, Page $page)
    {
        parent::__construct('layouts' . DS . $layout, $page);
    }

    /**
     * Get layout contents
     *
     * @return string|null
     */
    protected function content()
    {
        return $this->content;
    }
}
