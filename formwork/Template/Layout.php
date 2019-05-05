<?php

namespace Formwork\Template;

use Formwork\Core\Page;

class Layout extends Template
{
    /**
     * Template reference
     *
     * @var Template
     */
    protected $template;

    /**
     * Layout content
     *
     * @var string
     */
    protected $content;

    /**
     * Create a new Layout instance
     *
     * @param string   $layout
     * @param Page     $page
     * @param Template $template
     */
    public function __construct($layout, Page $page, Template $template)
    {
        parent::__construct('layouts' . DS . $layout, $page);
        $this->template = $template;
    }

    /**
     * @inheritdoc
     */
    public function path()
    {
        return $this->template->path();
    }

    /**
     * @inheritdoc
     */
    public function scheme()
    {
        return $this->template->scheme();
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
