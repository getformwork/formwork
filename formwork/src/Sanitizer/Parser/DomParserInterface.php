<?php

namespace Formwork\Sanitizer\Parser;

use DOMDocumentFragment;
use DOMNode;

interface DomParserInterface
{
    public function parse(string $string): ?DOMDocumentFragment;

    public function serialize(?DOMNode $domNode = null): string;
}
