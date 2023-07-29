<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

class Html
{
    use StaticClass;

    /**
     * Void HTML elements without content and end tag
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
     */
    protected const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    /**
     * Return an attribute ('name="value"') from $name and $value arguments
     */
    public static function attribute(string $name, $value = null): string
    {
        $name = strtolower($name);
        if ($value === true) {
            return $name;
        }
        if ($value === null || $value === false) {
            return '';
        }
        if (is_array($value)) {
            $value = implode(' ', array_filter($value));
        }
        return $name . '="' . Str::escapeAttr($value) . '"';
    }

    /**
     * Return an attributes string from an array of name and value pairs
     */
    public static function attributes(array $data): string
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[] = static::attribute($key, $value);
        }
        return implode(' ', array_filter($attributes));
    }

    /**
     * Return a string containing an HTML tag with specified name, attributes and content
     */
    public static function tag(string $name, array $attributes = [], ?string ...$content): string
    {
        $name = strtolower($name);
        $attributes = static::attributes($attributes);
        $html = '<' . $name;
        if ($attributes !== '') {
            $html .= ' ' . $attributes;
        }
        $html .= '>';
        if (count($content) > 0) {
            if (static::isVoid($name)) {
                throw new InvalidArgumentException(sprintf('Cannot set tag content, <%s> is a void element', $name));
            }
            $html .= implode('', $content);
        }
        if (!static::isVoid($name)) {
            $html .= '</' . $name . '>';
        }
        return $html;
    }

    /**
     * Return whether the given tag is a void element
     */
    public static function isVoid(string $tag): bool
    {
        return in_array(strtolower($tag), self::VOID_ELEMENTS, true);
    }
}
