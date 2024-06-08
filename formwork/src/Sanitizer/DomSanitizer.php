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
     * @var list<string>
     */
    protected array $allowedElements = [];

    /**
     * @var list<string>
     */
    protected array $allowedAttributes = [];

    /**
     * @var list<string>
     */
    protected array $uriAttributes = [];

    /**
     * @var list<string>
     */
    protected array $allowedUriSchemes = ['http', 'https'];

    /**
     * @var array<string, class-string<DomSanitizer>>
     */
    protected array $elementSanitizers = [];

    protected SanitizeElementsMethod $sanitizeElementsMethod = SanitizeElementsMethod::Remove;

    protected bool $allowExternalUris = true;

    public function __construct(protected DomParserInterface $domParser = new Html5Parser())
    {
    }

    public function sanitizeElementsMethod(SanitizeElementsMethod $sanitizeElementsMethod): static
    {
        $this->sanitizeElementsMethod = $sanitizeElementsMethod;
        return $this;
    }

    public function disallowExternalUris(): static
    {
        $this->allowExternalUris = false;
        return $this;
    }

    /**
     * @param list<string> $elements
     */
    public function allowedElements(array $elements): static
    {
        $this->allowedElements = $elements;
        return $this;
    }

    /**
     * @param list<string> $attributes
     */
    public function allowedAttributes(array $attributes): static
    {
        $this->allowedAttributes = $attributes;
        return $this;
    }

    /**
     * @param list<string> $schemes
     */
    public function allowedUriSchemes(array $schemes): static
    {
        $this->allowedUriSchemes = $schemes;
        return $this;
    }

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

    protected function isValidData(string $data): bool
    {
        return $data === '' || preg_match('//u', $data);
    }

    protected function isValidDocument(?DOMDocumentFragment $domDocumentFragment): bool
    {
        return $domDocumentFragment !== null;
    }

    protected function sanitizeDocumentFragment(DOMDocumentFragment $domDocumentFragment): void
    {
        $this->sanitizeNodes($domDocumentFragment->childNodes);
    }

    /**
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

    protected function sanitizeNodeAttributes(DOMElement $domElement): void
    {
        $attributes = $domElement->attributes;

        for ($i = $attributes->length; $i >= 0; $i--) {
            $attribute = $attributes->item($i);

            if (!($attribute instanceof DOMAttr)) {
                continue;
            }

            if (!in_array($attribute->nodeName, $this->allowedAttributes, true)) {
                $domElement->removeAttribute($attribute->nodeName);
            }

            if (in_array($attribute->nodeName, $this->uriAttributes, true)) {
                $uri = $this->sanitizeUri((string) $attribute->nodeValue);

                $scheme = Uri::scheme($uri);

                if ($scheme === null && !Str::startsWith($uri, '//')) {
                    continue;
                }

                if (!$this->allowExternalUris || !in_array($scheme, $this->allowedUriSchemes, true)) {
                    $domElement->removeAttribute($attribute->nodeName);
                }
            }
        }
    }

    protected function sanitizeUri(string $uri): string
    {
        $uri = urldecode($uri);

        $uri = preg_replace('/&(?:#\d+|#[xX][a-fA-F0-9]+|AElig|AMP|Aacute|Acirc|Agrave|Aring|Atilde|Auml|COPY|Ccedil|ETH|Eacute|Ecirc|Egrave|Euml|GT|Iacute|Icirc|Igrave|Iuml|LT|Ntilde|Oacute|Ocirc|Ograve|Oslash|Otilde|Ouml|QUOT|REG|THORN|Uacute|Ucirc|Ugrave|Uuml|Yacute|aacute|acirc|acute|aelig|agrave|amp|aring|atilde|auml|brvbar|ccedil|cedil|cent|copy|curren|deg|divide|eacute|ecirc|egrave|eth|euml|frac12|frac14|frac34|gt|iacute|icirc|iexcl|igrave|iquest|iuml|laquo|lt|macr|micro|middot|nbsp|not|ntilde|oacute|ocirc|ograve|ordf|ordm|oslash|otilde|ouml|para|plusmn|pound|quot|raquo|reg|sect|shy|sup1|sup2|sup3|szlig|thorn|times|uacute|ucirc|ugrave|uml|uuml|yacute|yen|yuml)(?!;)/', '$0;', $uri)
            ?? throw new RuntimeException('Cannot replace malformed HTML entities');

        $uri = html_entity_decode($uri);

        return filter_var($uri, FILTER_SANITIZE_URL)
            ?: throw new RuntimeException('Cannot sanitize URI');
    }
}
