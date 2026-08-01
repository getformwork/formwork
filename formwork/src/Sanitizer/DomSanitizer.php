<?php

namespace Formwork\Sanitizer;

use DOMAttr;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use Formwork\Sanitizer\Parser\DomParserInterface;
use Formwork\Sanitizer\Parser\Html5Parser;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class DomSanitizer
{
    /**
     * Allowed elements
     *
     * @var list<string>
     */
    protected array $allowedElements = [];

    /**
     * Allowed attributes
     *
     * @var list<string>
     */
    protected array $allowedAttributes = [];

    /**
     * URI attributes
     *
     * @var list<string>
     */
    protected array $uriAttributes = [];

    /**
     * Allowed URI schemes
     *
     * @var list<string>
     */
    protected array $allowedUriSchemes = ['http', 'https'];

    /**
     * Element sanitizers
     *
     * @var array<string, class-string<DomSanitizer>>
     */
    protected array $elementSanitizers = [];

    /**
     * Method used to sanitize elements
     */
    protected SanitizeElementsMethod $sanitizeElementsMethod = SanitizeElementsMethod::Remove;

    /**
     * Whether to allow external URIs
     */
    protected bool $allowExternalUris = true;

    public function __construct(
        protected DomParserInterface $domParser = new Html5Parser(),
    ) {}

    /**
     * Set the method used to sanitize elements
     */
    public function sanitizeElementsMethod(SanitizeElementsMethod $sanitizeElementsMethod): static
    {
        $this->sanitizeElementsMethod = $sanitizeElementsMethod;
        return $this;
    }

    /**
     * Disallow external URIs
     */
    public function disallowExternalUris(): static
    {
        $this->allowExternalUris = false;
        return $this;
    }

    /**
     * Set the allowed elements
     *
     * @param list<string> $elements
     */
    public function allowedElements(array $elements): static
    {
        $this->allowedElements = $elements;
        return $this;
    }

    /**
     * Set the allowed attributes
     *
     * @param list<string> $attributes
     */
    public function allowedAttributes(array $attributes): static
    {
        $this->allowedAttributes = $attributes;
        return $this;
    }

    /**
     * Set the allowed URI schemes
     *
     * @param list<string> $schemes
     */
    public function allowedUriSchemes(array $schemes): static
    {
        $this->allowedUriSchemes = $schemes;
        return $this;
    }

    /**
     * Sanitize a string containing markup
     */
    public function sanitize(string $data): string
    {
        if (!$this->isValidData($data)) {
            throw new InvalidArgumentException('Invalid data to sanitize');
        }

        $dom = $this->domParser->parse($data);

        if ($dom === null || !$this->isValidDocument($dom)) {
            throw new UnexpectedValueException('Invalid parsed DOM document');
        }

        $this->sanitizeDocumentFragment($dom);

        return $this->domParser->serialize($dom);
    }

    /**
     * Return whether data is valid Unicode
     */
    protected function isValidData(string $data): bool
    {
        return $data === '' || preg_match('//u', $data);
    }

    /**
     * Return whether a DOM document fragment is valid
     */
    protected function isValidDocument(?DOMDocumentFragment $domDocumentFragment): bool
    {
        return $domDocumentFragment !== null;
    }

    /**
     * Sanitize a DOM document fragment
     */
    protected function sanitizeDocumentFragment(DOMDocumentFragment $domDocumentFragment): void
    {
        $this->sanitizeNodes($domDocumentFragment->childNodes);
    }

    /**
     * Sanitize a DOM node list
     *
     * @param DOMNodeList<DOMNode> $domNodeList
     */
    protected function sanitizeNodes(DOMNodeList $domNodeList): void
    {
        for ($i = $domNodeList->length; $i >= 0; $i--) {
            $node = $domNodeList->item($i);

            if (!($node instanceof DOMElement)) {
                continue;
            }

            $this->sanitizeNode($node);
        }
    }

    /**
     * Sanitize a DOM element
     */
    protected function sanitizeNode(DOMElement $domElement): void
    {
        if (!in_array($domElement->nodeName, $this->allowedElements, true)) {
            if ($this->sanitizeElementsMethod === SanitizeElementsMethod::Escape) {
                if ($domElement->parentNode === null) {
                    throw new UnexpectedValueException('Missing parent node');
                }
                $domElement->parentNode->replaceChild(new DOMText($this->domParser->serialize($domElement)), $domElement);
            } else {
                $domElement->remove();
            }
            return;
        }

        if (isset($this->elementSanitizers[$domElement->nodeName])) {
            $sanitizer = $this->elementSanitizers[$domElement->nodeName];
            (new $sanitizer())->sanitizeNode($domElement);
            return;
        }

        if ($domElement->hasAttributes()) {
            $this->sanitizeNodeAttributes($domElement);
        }

        if ($domElement->hasChildNodes()) {
            $this->sanitizeNodes($domElement->childNodes);
        }
    }

    /**
     * Sanitize a DOM element attributes
     */
    protected function sanitizeNodeAttributes(DOMElement $domElement): void
    {
        $attributes = $domElement->attributes;

        // @phpstan-ignore identical.alwaysFalse
        if ($attributes === null) {
            throw new UnexpectedValueException('Missing attributes');
        }

        for ($i = $attributes->length; $i >= 0; $i--) {
            $attribute = $attributes->item($i);

            if (!($attribute instanceof DOMAttr)) {
                continue;
            }

            $this->sanitizeNodeAttribute($domElement, $attribute);
        }
    }

    /**
     * Sanitize a single DOM element attribute
     */
    protected function sanitizeNodeAttribute(DOMElement $domElement, DOMAttr $domAttr): void
    {
        if (!in_array($domAttr->nodeName, $this->allowedAttributes, true)) {
            $domElement->removeAttribute($domAttr->nodeName);
            return;
        }

        if (in_array($domAttr->nodeName, $this->uriAttributes, true)) {
            $uri = $this->sanitizeUri((string) $domAttr->nodeValue);

            $scheme = Uri::scheme($uri);

            if ($scheme === null && !Str::startsWith($uri, '//')) {
                return;
            }

            if (!$this->allowExternalUris || !in_array($scheme, $this->allowedUriSchemes, true)) {
                $domElement->removeAttribute($domAttr->nodeName);
            }
        }
    }

    /**
     * Sanitize a URI by removing invalid characters and decoding HTML entities
     */
    protected function sanitizeUri(string $uri): string
    {
        $uri = rawurldecode($uri);

        $uri = preg_replace('/&(?:#\d+|#[xX][a-fA-F0-9]+|AElig|AMP|Aacute|Acirc|Agrave|Aring|Atilde|Auml|COPY|Ccedil|ETH|Eacute|Ecirc|Egrave|Euml|GT|Iacute|Icirc|Igrave|Iuml|LT|Ntilde|Oacute|Ocirc|Ograve|Oslash|Otilde|Ouml|QUOT|REG|THORN|Uacute|Ucirc|Ugrave|Uuml|Yacute|aacute|acirc|acute|aelig|agrave|amp|aring|atilde|auml|brvbar|ccedil|cedil|cent|copy|curren|deg|divide|eacute|ecirc|egrave|eth|euml|frac12|frac14|frac34|gt|iacute|icirc|iexcl|igrave|iquest|iuml|laquo|lt|macr|micro|middot|nbsp|not|ntilde|oacute|ocirc|ograve|ordf|ordm|oslash|otilde|ouml|para|plusmn|pound|quot|raquo|reg|sect|shy|sup1|sup2|sup3|szlig|thorn|times|uacute|ucirc|ugrave|uml|uuml|yacute|yen|yuml)(?!;)/', '$0;', $uri)
            ?? throw new RuntimeException('Cannot replace malformed HTML entities');

        $uri = html_entity_decode($uri);

        if (($uri = filter_var($uri, FILTER_SANITIZE_URL)) === false) {
            throw new RuntimeException('Cannot sanitize URI');
        }

        return $uri;
    }
}
