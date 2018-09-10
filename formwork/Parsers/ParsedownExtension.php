<?php

namespace Formwork\Parsers;

use Formwork\Core\Formwork;
use Formwork\Core\Page;
use Formwork\Utils\Uri;
use ParsedownExtra;

class ParsedownExtension extends ParsedownExtra
{
    protected $page;

    public function setPage(Page $page)
    {
        $this->page = $page;
    }

    protected function inlineLink($excerpt)
    {
        $link = parent::inlineLink($excerpt);
        if (!isset($link)) {
            return;
        }
        $href = &$link['element']['attributes']['href'];
        if (empty(Uri::host($href)) && $href[0] !== '#') {
            $relativeUri = Uri::resolveRelativeUri($href, $this->page->slug());
            $href = Formwork::instance()->site()->uri($relativeUri);
        }
        return $link;
    }
}
