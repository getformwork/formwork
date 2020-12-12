<?php

namespace Formwork\Parsers\Extensions;

use Formwork\Core\Formwork;
use Formwork\Utils\Uri;

class ParsedownExtra extends \ParsedownExtra
{
    /**
     * Base route to resolve links
     *
     * @var string
     */
    protected $baseRoute;

    /**
     * @inheritdoc
     */
    public function text($text, array $options = [])
    {
        $this->baseRoute = $options['baseRoute'] ?? '/';
        return parent::text($text);
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
        if (in_array(Uri::scheme($href), [null, 'http', 'https'], true) && (empty(Uri::host($href)) && $href[0] !== '#')) {
            $relativeUri = Uri::resolveRelative($href, $this->baseRoute);
            $href = Formwork::instance()->site()->uri($relativeUri, false);
        }
        return $link;
    }
}
