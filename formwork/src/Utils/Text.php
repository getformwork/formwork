<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use RuntimeException;

class Text
{
    use StaticClass;

    /**
     * Regex matching whitespace characters
     */
    protected const WHITESPACE_REGEX = '/[\s\xb\p{Z}]+/u';

    /**
     * Normalized whitespace sequence
     */
    protected const WHITESPACE_SEQUENCE = ' ';

    /**
     * Default sequence appended when text is truncated
     */
    protected const DEFAULT_ELLIPSIS_SEQUENCE = '…';

    /**
     * Default words per minute used to determine reading time
     */
    protected const DEFAULT_WORDS_PER_MINUTE = 180;

    /**
     * Normalize whitespace of a given text
     */
    public static function normalizeWhitespace(string $text): string
    {
        return preg_replace(self::WHITESPACE_REGEX, self::WHITESPACE_SEQUENCE, $text)
            ?? throw new RuntimeException(sprintf('Whitespace replacement failed with error: %s', preg_last_error_msg()));
    }

    /**
     * Split a text into words
     *
     * @return array<string>
     */
    public static function splitWords(string $text, ?int $limit = null): array
    {
        return explode(self::WHITESPACE_SEQUENCE, static::normalizeWhitespace($text), $limit ?? PHP_INT_MAX);
    }

    /**
     * Count the words of a given text
     */
    public static function countWords(string $text): int
    {
        return count(static::splitWords($text));
    }

    /**
     * Truncate a given text up to a length, preserving words and appending ellipsis sequence if characters were removed
     */
    public static function truncate(string $text, int $length, string $ellipsis = self::DEFAULT_ELLIPSIS_SEQUENCE): string
    {
        if (!extension_loaded('mbstring')) {
            throw new RuntimeException(sprintf('%s() requires the extension "mbstring" to be enabled', __METHOD__));
        }

        $text = static::normalizeWhitespace($text);

        if ($length >= mb_strlen($text)) {
            return $text;
        }

        $text = mb_substr($text, 0, $length + 1);
        return mb_substr($text, 0, mb_strrpos($text, self::WHITESPACE_SEQUENCE) ?: null) . $ellipsis;
    }

    /**
     * Truncate a given text leaving a given number of words, appending ellipsis sequence if content was removed
     */
    public static function truncateWords(string $text, int $count, string $ellipsis = self::DEFAULT_ELLIPSIS_SEQUENCE): string
    {
        $words = static::splitWords($text);
        $result = implode(self::WHITESPACE_SEQUENCE, array_slice($words, 0, $count));
        return count($words) <= $count ? $result : $result . $ellipsis;
    }

    /**
     * Estimate reading time of a text in minutes
     */
    public static function readingTime(string $text, int $wordsPerMinute = self::DEFAULT_WORDS_PER_MINUTE): int
    {
        return max(1, (int) round(static::countWords($text) / $wordsPerMinute));
    }
}
