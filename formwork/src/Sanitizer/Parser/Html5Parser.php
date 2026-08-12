<?php

namespace Formwork\Sanitizer\Parser;

use DOMDocumentFragment;
use DOMNode;
use Masterminds\HTML5;
use RuntimeException;

class Html5Parser implements DomParserInterface
{
    protected HTML5 $dom;

    public function __construct()
    {
        if (!extension_loaded('dom')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "dom" to be enabled', static::class));
        }

        $this->dom = new HTML5();
    }

    public function parse(string $string): ?DOMDocumentFragment
    {
        return $this->dom->loadHTMLFragment($string);
    }

    public function serialize(?DOMNode $domNode = null): string
    {
        return $this->dom->saveHTML($domNode);
    }
}
