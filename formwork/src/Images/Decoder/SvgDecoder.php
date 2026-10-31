<?php

namespace Formwork\Images\Decoder;

use DOMDocument;
use DOMElement;
use Generator;
use InvalidArgumentException;
use RuntimeException;

final class SvgDecoder implements DecoderInterface
{
    public function __construct()
    {
        if (!extension_loaded('dom')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "dom" to be enabled', self::class));
        }
    }

    public function decode(string &$data): Generator
    {
        if ($data === '') {
            throw new InvalidArgumentException('Invalid SVG data');
        }

        $domDocument = new DOMDocument();

        $domDocument->loadXML($data, LIBXML_NOERROR);

        $root = $domDocument->documentElement;

        if (!$root instanceof DOMElement || $root->localName !== 'svg') {
            throw new InvalidArgumentException('Invalid SVG data');
        }

        $width = $this->parseLength($root->getAttribute('width'));
        $height = $this->parseLength($root->getAttribute('height'));

        // Fall back to the view box when the size is missing or relative
        if ($width === null || $height === null) {
            $viewBox = $this->parseViewBox($root->getAttribute('viewBox'));

            if ($viewBox === null) {
                return;
            }

            [$boxWidth, $boxHeight] = $viewBox;

            $width ??= $height !== null && $boxHeight > 0 ? $height * $boxWidth / $boxHeight : $boxWidth;
            $height ??= $boxWidth > 0 ? $width * $boxHeight / $boxWidth : $boxHeight;
        }

        yield [
            'width'  => (int) round($width),
            'height' => (int) round($height),
        ];
    }

    /**
     * Parse an absolute length and convert it to pixels
     *
     * Relative lengths (percentages and font-relative units) cannot be resolved without rendering the image.
     */
    private function parseLength(string $length): ?float
    {
        if (preg_match('/^\s*(\d+(?:\.\d+)?|\.\d+)(px|pt|pc|mm|cm|in)?\s*$/i', $length, $matches) !== 1) {
            return null;
        }

        $value = (float) $matches[1];

        return $value * match (strtolower($matches[2] ?? '')) {
            'pt'    => 96 / 72,
            'pc'    => 16,
            'mm'    => 96 / 25.4,
            'cm'    => 96 / 2.54,
            'in'    => 96,
            default => 1,
        };
    }

    /**
     * Parse the width and the height of a view box
     *
     * @return ?array{float, float}
     */
    private function parseViewBox(string $viewBox): ?array
    {
        $values = preg_split('/[\s,]+/', trim($viewBox), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($values) !== 4 || !is_numeric($values[2]) || !is_numeric($values[3])) {
            return null;
        }

        return [max(0.0, (float) $values[2]), max(0.0, (float) $values[3])];
    }
}
