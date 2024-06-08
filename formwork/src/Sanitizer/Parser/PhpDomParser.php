<?php

namespace Formwork\Sanitizer\Parser;

use DOMDocument;
use DOMDocumentFragment;
use DOMNode;

class PhpDomParser implements DomParserInterface
{
    protected DOMDocument $dom;

    public function __construct()
    {
        $this->dom = new DOMDocument();
    }

    public function parse(string $string): ?DOMDocumentFragment
    {
        $domDocumentFragment = $this->dom->createDocumentFragment();

        if (!@$domDocumentFragment->appendXML($string)) {
            return null;
        }

        return $domDocumentFragment;
    }

    public function serialize(?DOMNode $domNode = null): string
    {
        if ($domNode instanceof DOMDocument) {
            $domNode = $domNode->firstElementChild;
        }
        return (string) $this->dom->saveXML($domNode);
    }
}
