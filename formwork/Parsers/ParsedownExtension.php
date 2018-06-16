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

    protected function inlineImage($excerpt) {
        $image = parent::inlineImage($excerpt);
        if (!isset($image)) return null;

        $src = $image['element']['attributes']['src'];

        if (strlen($src) > 0) {
            if ($src[0] == '/') {
                $image['element']['attributes']['src'] = Formwork::instance()->site()->uri($src);
            } else {
                $image['element']['attributes']['src'] = $this->page->uri() . $src;
            }
        }

        return $image;
    }

    protected function inlineLink($excerpt) {
        $link = parent::inlineLink($excerpt);
        if (!isset($link)) return null;

        $href = $link['element']['attributes']['href'];

        if (!empty($href)) {
            if ($href[0] == '/') {
                $link['element']['attributes']['href'] = Formwork::instance()->site()->uri($href);
            }
        }

        return $link;
    }

}
