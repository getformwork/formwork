<?php

namespace Formwork\Utils;

class HTTPNegotiation
{
    /**
     * Return an array containing client preferred MIME types as keys and quality factors as values
     */
    public static function mimeType(): array
    {
        return static::parseHeader('Accept');
    }

    /**
     * Return an array containing client preferred charsets as keys and quality factors as values
     */
    public static function charset(): array
    {
        return static::parseHeader('Accept-Charset');
    }

    /**
     * Return an array containing client preferred encodings as keys and quality factors as values
     */
    public static function encoding(): array
    {
        return static::parseHeader('Accept-Encoding');
    }

    /**
     * Return an array containing client preferred languages as keys and quality factors as values
     */
    public static function language(): array
    {
        return static::parseHeader('Accept-Language');
    }

    /**
     * Parse a given header returning an associative array with quality factor as values
     */
    public static function parseHeader(string $header): array
    {
        if (!HTTPRequest::hasHeader($header)) {
            return [];
        }
        return static::parseCommaSeparatedList(HTTPRequest::headers()[$header]);
    }

    /**
     * Parse a comma-separated list of values and quality factors returning an associative array
     *
     * @see https://developer.mozilla.org/docs/Glossary/Quality_values
     */
    protected static function parseCommaSeparatedList(string $list): array
    {
        $result = [];
        $tokens = array_map('trim', explode(',', $list));
        foreach ($tokens as $token) {
            $item = preg_split('/\s*;\s*q=/', $token);
            $value = $item[0];
            $factor = isset($item[1]) ? round($item[1], 3) : 1;
            $result[$value] = $factor;
        }
        arsort($result);
        return $result;
    }
}
