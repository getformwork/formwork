<?php

namespace Formwork\Parsers;
use Formwork\Core\Formwork;
use Formwork\Core\Page;
use ParsedownExtra;

class ParsedownExtension extends ParsedownExtra {

    protected $page;

    public function setPage(Page $page) {
        $this->page = $page;
    }

    protected function inlineLink($excerpt) {
        $link = parent::inlineLink($excerpt);

        if (!isset($link)) return;

        $href = &$link['element']['attributes']['href'];

        if ($href[0] == '/' && strpos($href, '//') === false) {
            $href = Formwork::instance()->site()->uri($href);
        } elseif (!preg_match('~^([a-z]+:)?//~i', $href)) {
            $href = $this->page->uri() . $href;
        }

        return $link;
    }

}
