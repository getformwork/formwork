<?php

namespace Formwork\Sanitizer;

use DOMAttr;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use Formwork\Sanitizer\Parser\DomParserInterface;
use Formwork\Sanitizer\Parser\PhpDomParser;
use Formwork\Sanitizer\Reference\SvgReference;
use UnexpectedValueException;

class SvgSanitizer extends DomSanitizer
{
    protected array $allowedElements = SvgReference::ALLOWED_ELEMENTS;

    protected array $allowedAttributes = SvgReference::ALLOWED_ATTRIBUTES;

    protected array $uriAttributes = SvgReference::URI_ATTRIBUTES;

    public function __construct(protected DomParserInterface $domParser = new PhpDomParser())
    {
    }

    protected function isValidDocument(?DOMDocumentFragment $domDocumentFragment): bool
    {
        if ($domDocumentFragment === null) {
            return false;
        }

        if ($domDocumentFragment->childElementCount > 1) {
            return false;
        }

        return $domDocumentFragment->firstElementChild?->nodeName === 'svg';
    }

    protected function sanitizeDocumentFragment(DOMDocumentFragment $domDocumentFragment): void
    {
        parent::sanitizeDocumentFragment($domDocumentFragment);
        $this->addExplicitSvgNamespace($domDocumentFragment);
    }

    protected function addExplicitSvgNamespace(DOMDocumentFragment $domDocumentFragment): void
    {
        $svg = $domDocumentFragment->firstElementChild;

        if (!($svg instanceof DOMElement)) {
            throw new UnexpectedValueException('Invalid SVG document');
        }

        if ($svg->namespaceURI === SvgReference::NAMESPACE_URI) {
            return;
        }

        $document = $domDocumentFragment->ownerDocument;

        if (!($document instanceof DOMDocument)) {
            throw new UnexpectedValueException('Unexpected missing SVG DOM document');
        }

        $domElement = $document->createElementNS(SvgReference::NAMESPACE_URI, 'svg');

        $attributes = $svg->attributes;

        for ($i = $attributes->length; $i >= 0; $i--) {
            $attribute = $attributes->item($i);
            if ($attribute instanceof DOMAttr) {
                $domElement->setAttributeNode($attribute);
            }
        }

        $domElement->append(...$svg->childNodes);

        $svg->replaceWith($domElement);
    }
}
