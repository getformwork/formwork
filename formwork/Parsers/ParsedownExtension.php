<?php

namespace Formwork\Parsers;

use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Utils\Uri;
use ParsedownExtra;

class ParsedownExtension extends ParsedownExtra
{
    /**
     * Page that will be parsed
     *
     * @var Page
     */
    protected $page;

    /**
     * Set the page that will be parsed
     *
     * @param Page $page
     */
    public function setPage(Page $page)
    {
        $this->page = $page;
    }

    /**
     * @inheritdoc
     */
    protected function inlineLink($excerpt)
    {
        $link = parent::inlineLink($excerpt);
        if (!isset($link)) {
            return;
        }
        $href = &$link['element']['attributes']['href'];
        // Process only if scheme is either null, 'http' or 'https'
        if (in_array(Uri::scheme($href), array(null, 'http', 'https'), true)) {
            if (empty(Uri::host($href)) && $href[0] !== '#') {
                $relativeUri = Uri::resolveRelativeUri($href, $this->page->route());
                $href = Formwork::instance()->site()->uri($relativeUri, false);
            }
        }
        return $link;
    }
}
