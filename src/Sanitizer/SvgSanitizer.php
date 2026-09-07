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

    /**
     * @var list<string>
     */
    protected array $smilElements = SvgReference::SMIL_ELEMENTS;

    public function __construct(
        protected DomParserInterface $domParser = new PhpDomParser(),
    ) {}

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

    protected function sanitizeNodeAttribute(DOMElement $domElement, DOMAttr $domAttr): void
    {
        parent::sanitizeNodeAttribute($domElement, $domAttr);

        if (
            in_array($domElement->nodeName, $this->smilElements, true)
            && $domAttr->name === 'attributeName'
            && !$this->isSafeSmilAttributeName($domAttr->value)
        ) {
            $domElement->removeAttribute($domAttr->name);
        }
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

        // @phpstan-ignore identical.alwaysFalse
        if ($attributes === null) {
            throw new UnexpectedValueException('Missing attributes');
        }

        for ($i = $attributes->length; $i >= 0; $i--) {
            $attribute = $attributes->item($i);
            if ($attribute instanceof DOMAttr) {
                $domElement->setAttributeNode($attribute);
            }
        }

        $domElement->append(...$svg->childNodes);

        $svg->replaceWith($domElement);
    }

    /**
     * Return whether the SMIL `attributeName` is allowed and not a URI attribute (which would be unsafe)
     */
    private function isSafeSmilAttributeName(string $attributeName): bool
    {
        return in_array($attributeName, $this->allowedAttributes, true)
            && !in_array($attributeName, $this->uriAttributes, true);
    }
}
