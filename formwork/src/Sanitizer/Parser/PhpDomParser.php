<?php

namespace Formwork\Sanitizer\Parser;

use DOMDocument;
use DOMDocumentFragment;
use DOMNode;
use RuntimeException;

class PhpDomParser implements DomParserInterface
{
    protected DOMDocument $dom;

    public function __construct()
    {
        if (!extension_loaded('dom')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "dom" to be enabled', static::class));
        }

        $this->dom = new DOMDocument();
    }

    public function parse(string $string): ?DOMDocumentFragment
    {
        $domDocumentFragment = $this->dom->createDocumentFragment();

        if (!@$domDocumentFragment->appendXML($this->removeProlog($string))) {
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

    /**
     * Remove the document prolog (byte order mark, XML declaration, processing instructions,
     * document type declaration and comments before the root element)
     *
     * Document fragments cannot contain a prolog, so it would make the whole markup invalid.
     * The document type declaration is discarded with any entity it defines.
     */
    protected function removeProlog(string $string): string
    {
        return preg_replace('/^(?:\xEF\xBB\xBF|\s+|<\?.*?\?>|<!DOCTYPE\b[^\[>]*(?:\[.*?\])?\s*>|<!--.*?-->)+/s', '', $string) ?? $string;
    }
}
