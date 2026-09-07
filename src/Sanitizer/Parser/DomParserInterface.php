<?php

namespace Formwork\Sanitizer\Parser;

use DOMDocumentFragment;
use DOMNode;

interface DomParserInterface
{
    /**
     * Parse a string into a DOMDocumentFragment
     */
    public function parse(string $string): ?DOMDocumentFragment;

    /**
     * Serialize a DOMNode into a string
     */
    public function serialize(?DOMNode $domNode = null): string;
}
