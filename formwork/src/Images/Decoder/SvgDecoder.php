<?php

namespace Formwork\Images\Decoder;

use DOMDocument;
use DOMElement;
use Generator;
use InvalidArgumentException;

class SvgDecoder implements DecoderInterface
{
    public function decode(string &$data): Generator
    {
        $domDocument = new DOMDocument();

        $domDocument->loadXML($data, LIBXML_NOERROR);

        $root = $domDocument->documentElement;

        if (!($root instanceof DOMElement && $root->nodeName === 'svg')) {
            throw new InvalidArgumentException('Invalid SVG data');
        }

        if (!$root->hasAttribute('width')) {
            return;
        }

        if (!$root->hasAttribute('height')) {
            return;
        }

        yield [
            'width'  => $root->getAttribute('width'),
            'height' => $root->getAttribute('height'),
        ];
    }
}
