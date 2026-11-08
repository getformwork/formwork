<?php

namespace Formwork\Sanitizer;

use DOMAttr;
use DOMComment;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMProcessingInstruction;
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

    protected function sanitizeNode(DOMElement $domElement): void
    {
        if ($domElement->nodeName === 'style' && !$this->isSafeStyle($domElement)) {
            $domElement->remove();
            return;
        }

        parent::sanitizeNode($domElement);
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
            return;
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
     * Return whether a `<style>` element cannot hide markup from the HTML parser of browsers
     */
    private function isSafeStyle(DOMElement $domElement): bool
    {
        // Browsers end comments and bogus comments early in foreign content (e.g. at `<!-->`), so they cannot be kept
        for ($i = $domElement->childNodes->length - 1; $i >= 0; $i--) {
            $childNode = $domElement->childNodes->item($i);
            if ($childNode instanceof DOMComment || $childNode instanceof DOMProcessingInstruction) {
                $domElement->removeChild($childNode);
            }
        }

        // Standalone SVG serialized as XML has its text escaped, so markup cannot be injected
        if (!$this->embedded && $this->domParser instanceof PhpDomParser) {
            return true;
        }

        // Embedded SVG is serialized as HTML, where style text is raw
        $text = $this->unwrapCdata($domElement->textContent);

        // Fail closed if the text cannot be checked
        return $text !== null && preg_match('/<[a-z\/!?]/i', $text) === 0;
    }

    /**
     * Replace complete CDATA sections with their content, or return `null` if one is unterminated
     *
     * Raw-text parsers keep CDATA delimiters as text, but browsers read them in foreign content.
     * An unterminated section is not unwrapped, as browsers would end it at a later `]]>`.
     */
    private function unwrapCdata(string $text): ?string
    {
        $result = '';
        $offset = 0;

        while (($start = strpos($text, '<![CDATA[', $offset)) !== false) {
            $contentStart = $start + strlen('<![CDATA[');
            $end = strpos($text, ']]>', $contentStart);

            if ($end === false) {
                return null;
            }

            $result .= substr($text, $offset, $start - $offset) . substr($text, $contentStart, $end - $contentStart);
            $offset = $end + strlen(']]>');
        }

        return $result . substr($text, $offset);
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
