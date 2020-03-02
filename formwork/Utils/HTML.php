<?php

namespace Formwork\Utils;

use InvalidArgumentException;

class HTML
{
    /**
     * Void HTML elements without content and end tag
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
     *
     * @var array
     */
    protected const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    /**
     * Return an attribute ('name="value"') from $name and $value arguments
     *
     * @return string
     */
    public static function attribute(string $name, $value = null)
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
        return $name . '="' . $value . '"';
    }

    /**
     * Return an attributes string from an array of name and value pairs
     *
     * @return string
     */
    public static function attributes(array $data)
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[] = static::attribute($key, $value);
        }
        return implode(' ', array_filter($attributes));
    }

    /**
     * Return a string containing an HTML tag with specified name, attributes and content
     *
     * @return string
     */
    public static function tag(string $name, array $attributes = [], ?string $content = null)
    {
        $name = strtolower($name);
        $attributes = static::attributes($attributes);
        $html = '<' . $name;
        if ($attributes !== '') {
            $html .= ' ' . $attributes;
        }
        $html .= '>';
        if ($content !== null) {
            if (static::isVoid($name)) {
                throw new InvalidArgumentException('Cannot set tag content, <' . $name . '> is a void element');
            }
            $html .= $content . '</' . $name . '>';
        }
        return $html;
    }

    /**
     * Return whether the given tag is a void element
     *
     * @return bool
     */
    public static function isVoid(string $tag)
    {
        return in_array(strtolower($tag), self::VOID_ELEMENTS, true);
    }
}
