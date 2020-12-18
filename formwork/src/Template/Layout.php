<?php

namespace Formwork\Template;

use Formwork\Core\Page;
use Formwork\Schemes\Scheme;

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
     */
    public function __construct(string $layout, Page $page, Template $template)
    {
        parent::__construct('layouts' . DS . $layout, $page);
        $this->template = $template;
    }

    /**
     * @inheritdoc
     */
    public function path(): string
    {
        return $this->template->path();
    }

    /**
     * @inheritdoc
     */
    public function scheme(): Scheme
    {
        return $this->template->scheme();
    }

    /**
     * Get layout contents
     */
    public function content(): ?string
    {
        return $this->content;
    }
}
