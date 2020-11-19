<?php

namespace Formwork\Utils;

class Str
{
    /**
     * Translate map to make slugs
     *
     * @var array
     */
    protected const SLUG_TRANSLATE_MAP = [
        '\t' => '', '\r' => '', '!' => '', '"' => '', '#' => '', '$' => '', '%' => '', '\'' => '-', '(' => '', ')' => '', '*' => '', '+' => '', ',' => '', '.' => '', ':' => '', ';' => '', '<' => '', '=' => '', '>' => '', '?' => '', '@' => '', '[' => '', ']' => '', '^' => '', '`' => '', '{' => '', '|' => '', '}' => '', '¡' => '', '£' => '', '¤' => '', '¥' => '', '¦' => '', '§' => '', '«' => '', '°' => '', '»' => '', '‘' => '', '’' => '', '“' => '', '”' => '', '\n' => '-', ' ' => '-', '-' => '-', '–' => '-', '—' => '-', '/' => '-', '\\' => '-', '_' => '-', '~' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Œ' => 'Oe', 'Š' => 'S', 'Þ' => 'Th', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'æ' => 'ae', '¢' => 'c', 'ç' => 'c', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'œ' => 'oe', 'š' => 's', 'ß' => 'ss', 'þ' => 'th', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ý' => 'y', 'ÿ' => 'y', 'Ÿ' => 'y'
    ];

    /**
     * Return whether $haystack string starts with $needle
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Return whether $haystack string ends with $needle
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Escape HTML tags from a given string
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_COMPAT | ENT_SUBSTITUTE, 'utf-8', false);
    }

    /**
     * Remove HTML tags and entities from a given string
     */
    public static function removeHTML(string $string): string
    {
        return html_entity_decode(strip_tags($string), ENT_QUOTES | ENT_HTML5, 'utf-8');
    }

    /**
     * Make slug from a given string
     */
    public static function slug(string $string): string
    {
        return preg_replace(['/^-|-$|[^a-z0-9-]/', '/-+/'], ['', '-'], strtr(strtolower($string), self::SLUG_TRANSLATE_MAP));
    }

    /**
     * Wrap a string with another
     */
    public static function wrap(string $string, string $wrap): string
    {
        return (static::startsWith($string, $wrap) ? '' : $wrap) . $string . (static::endsWith($string, $wrap) ? '' : $wrap);
    }

    /**
     * Remove $needle from $haystack if it is at the beginning, otherwise return $haystack
     */
    public static function removeStart(string $haystack, string $needle): string
    {
        return static::startsWith($haystack, $needle) ? substr($haystack, strlen($needle)) : $haystack;
    }

    /**
     * Remove $needle from $haystack if it is at the end, otherwise return $haystack
     */
    public static function removeEnd(string $haystack, string $needle): string
    {
        return static::endsWith($haystack, $needle) ? substr($haystack, 0, -strlen($needle)) : $haystack;
    }
}
