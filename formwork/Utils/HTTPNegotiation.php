<?php

namespace Formwork\Utils;

class HTTPNegotiation
{
    /**
     * Return an array containing client preferred MIME types as keys and quality factors as values
     *
     * @return array
     */
    public static function mimeType()
    {
        return static::parseHeader('Accept');
    }

    /**
     * Return an array containing client preferred charsets as keys and quality factors as values
     *
     * @return array
     */
    public static function charset()
    {
        return static::parseHeader('Accept-Charset');
    }

    /**
     * Return an array containing client preferred encodings as keys and quality factors as values
     *
     * @return array
     */
    public static function encoding()
    {
        return static::parseHeader('Accept-Encoding');
    }

    /**
     * Return an array containing client preferred languages as keys and quality factors as values
     *
     * @return array
     */
    public static function language()
    {
        return static::parseHeader('Accept-Language');
    }

    /**
     * Parse a given header returning an associative array with quality factor as values
     *
     * @param string $header
     *
     * @return array
     */
    public static function parseHeader($header)
    {
        if (!HTTPRequest::hasHeader($header)) {
            return array();
        }
        return static::parseCommaSeparatedList(HTTPRequest::headers()[$header]);
    }

    /**
     * Parse a comma-separated list of values and quality factors returning an associative array
     *
     * @see https://developer.mozilla.org/docs/Glossary/Quality_values
     *
     * @param string $list
     *
     * @return array
     */
    protected static function parseCommaSeparatedList($list)
    {
        $result = array();
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
