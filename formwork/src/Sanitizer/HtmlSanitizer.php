<?php

namespace Formwork\Sanitizer;

use DOMAttr;
use DOMElement;
use Formwork\Sanitizer\Reference\HtmlReference;

class HtmlSanitizer extends DomSanitizer
{
    protected array $allowedElements = HtmlReference::ALLOWED_ELEMENTS;

    protected array $allowedAttributes = HtmlReference::ALLOWED_ATTRIBUTES;

    protected array $uriAttributes = HtmlReference::URI_ATTRIBUTES;

    /**
     * @var list<string>
     */
    protected array $allowedUriSchemes = ['http', 'https', 'mailto'];

    /**
     * @var array<string, class-string<DomSanitizer>>
     */
    protected array $elementSanitizers = [
        'svg' => SvgSanitizer::class,
    ];

    protected function sanitizeNodeAttribute(DOMElement $domElement, DOMAttr $domAttr): void
    {
        parent::sanitizeNodeAttribute($domElement, $domAttr);

        if (
            $domElement->nodeName === 'meta' && $domAttr->name === 'content'
            && $domElement->attributes->getNamedItem('http-equiv')?->nodeValue === 'refresh'
            && !$this->isSafeMetaRefresh((string) $domAttr->nodeValue)
        ) {
            $domElement->removeAttribute('content');
            return;
        }
    }

    protected function isSafeUriAttribute(DOMElement $domElement, DOMAttr $domAttr): bool
    {
        if (!in_array($domAttr->nodeName, ['srcset', 'imagesrcset'], true)) {
            return parent::isSafeUriAttribute($domElement, $domAttr);
        }

        $sources = explode(',', (string) $domAttr->nodeValue);

        foreach ($sources as $source) {
            if (
                preg_match('/^\s*(\S+)(\s*\d+[wx])?\s*$/', $source, $matches)
                && !$this->isSafeUri($matches[1])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return whether the given `<meta http-equiv="refresh" content="...">` is safe
     */
    private function isSafeMetaRefresh(string $content): bool
    {
        if (!preg_match('/^(?:\s*(\d+)\s*[,;](?:\s*url\s*=)?)(.+)(?:\s*)$/i', $content, $matches)) {
            return true;
        }

        return $this->isSafeUri($matches[2]);
    }
}
