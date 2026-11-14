<?php

namespace Formwork\Parsers;

final class Json extends AbstractEncoder
{
    /**
     * Default flags used to parse JSON
     */
    private const int DEFAULT_PARSE_FLAGS = JSON_THROW_ON_ERROR;

    /**
     * Default flags used to encode JSON
     */
    private const int DEFAULT_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    /**
     * Default options used to encode JSON
     *
     * @var array{forceObject: bool, prettyPrint: bool, escapeUnicode: bool, escapeHtml: bool}
     */
    private const array DEFAULT_ENCODE_OPTIONS = [
        'forceObject'   => false,
        'prettyPrint'   => false,
        'escapeUnicode' => false,
        'escapeHtml'    => false,
    ];

    /**
     * Parse a JSON string
     *
     * @param array<string, mixed> $options
     *
     * @return array<mixed>
     */
    public static function parse(string $input, array $options = []): array
    {
        return (array) json_decode($input, true, 512, self::DEFAULT_PARSE_FLAGS);
    }

    /**
     * Encode data to JSON format
     *
     * @param array<mixed>                                                                           $data
     * @param array{forceObject?: bool, prettyPrint?: bool, escapeUnicode?: bool, escapeHtml?: bool} $options
     */
    public static function encode($data, array $options = []): string
    {
        $options = [...self::DEFAULT_ENCODE_OPTIONS, ...$options];
        $flags = self::DEFAULT_ENCODE_FLAGS;
        if ($options['prettyPrint']) {
            $flags |= JSON_PRETTY_PRINT;
        }
        if (!$options['escapeUnicode']) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }
        if ($data === [] || $options['forceObject']) {
            $flags |= JSON_FORCE_OBJECT;
        }
        if ($options['escapeHtml']) {
            // Tags and ampersands are escaped, so that the data can be embedded in `<script>` elements
            $flags |= JSON_HEX_TAG | JSON_HEX_AMP;
        }
        return json_encode($data, $flags);
    }
}
