<?php

namespace Formwork\Sanitizer;

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
}
