<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use RuntimeException;

final class Text
{
    use StaticClass;

    /**
     * Regex matching whitespace characters
     */
    private const string WHITESPACE_REGEX = '/[\s\xb\p{Z}]+/u';

    /**
     * Normalized whitespace sequence
     */
    private const string WHITESPACE_SEQUENCE = ' ';

    /**
     * Default sequence appended when text is truncated
     */
    private const string DEFAULT_ELLIPSIS_SEQUENCE = '…';

    /**
     * Default words per minute used to determine reading time
     */
    private const int DEFAULT_WORDS_PER_MINUTE = 180;

    /**
     * Normalize whitespace of a given text
     *
     * @throws RuntimeException If the whitespace normalization fails
     */
    public static function normalizeWhitespace(string $text): string
    {
        $normalized = preg_replace(self::WHITESPACE_REGEX, self::WHITESPACE_SEQUENCE, $text)
            ?? throw new RuntimeException(sprintf('Whitespace replacement failed with error: %s', preg_last_error_msg()));
        return trim($normalized);
    }

    /**
     * Split a text into words
     *
     * @param int|null $limit Maximum number of words to return (null for no limit)
     *
     * @return array<string>
     */
    public static function splitWords(string $text, ?int $limit = null): array
    {
        $words = explode(self::WHITESPACE_SEQUENCE, self::normalizeWhitespace($text), $limit ?? PHP_INT_MAX);
        return $words === [''] ? [] : $words;
    }

    /**
     * Count the words of a given text
     */
    public static function countWords(string $text): int
    {
        return count(self::splitWords($text));
    }

    /**
     * Truncate a given text up to a length, preserving words and appending ellipsis sequence if characters were removed
     *
     * @param int    $length   Maximum length of the truncated text
     * @param string $ellipsis The ellipsis sequence to append when text is truncated
     *
     * @throws RuntimeException If the `mbstring` extension is not loaded
     */
    public static function truncate(string $text, int $length, string $ellipsis = self::DEFAULT_ELLIPSIS_SEQUENCE): string
    {
        if (!extension_loaded('mbstring')) {
            throw new RuntimeException(sprintf('%s() requires the extension "mbstring" to be enabled', __METHOD__));
        }

        $text = self::normalizeWhitespace($text);

        if ($length >= mb_strlen($text)) {
            return $text;
        }

        $text = mb_substr($text, 0, $length + 1);
        return mb_substr($text, 0, mb_strrpos($text, self::WHITESPACE_SEQUENCE) ?: null) . $ellipsis;
    }

    /**
     * Truncate a given text leaving a given number of words, appending ellipsis sequence if content was removed
     *
     * @param int    $count    Maximum number of words to keep
     * @param string $ellipsis The ellipsis sequence to append when text is truncated
     */
    public static function truncateWords(string $text, int $count, string $ellipsis = self::DEFAULT_ELLIPSIS_SEQUENCE): string
    {
        $words = self::splitWords($text);
        $result = implode(self::WHITESPACE_SEQUENCE, array_slice($words, 0, $count));
        return count($words) <= $count ? $result : $result . $ellipsis;
    }

    /**
     * Estimate reading time of a text in minutes
     *
     * @param string $text           The text to analyze
     * @param int    $wordsPerMinute Average reading speed in words per minute
     */
    public static function readingTime(string $text, int $wordsPerMinute = self::DEFAULT_WORDS_PER_MINUTE): int
    {
        return max(1, (int) round(self::countWords($text) / $wordsPerMinute));
    }
}
