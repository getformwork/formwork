<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

final class Html
{
    use StaticClass;

    /**
     * Void HTML elements without content and end tag
     *
     * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
     *
     * @var list<string>
     */
    private const array VOID_ELEMENTS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /**
     * Return a class list separated by spaces
     *
     * List items are used as class names, associative items keys are used
     * instead as class names for values that compare to `true`
     *
     * @param array<string, mixed>|list<string> $list
     */
    public static function classes(array $list = []): string
    {
        $items = [];
        foreach ($list as $key => $value) {
            if (!is_string($key)) {
                $items[] = $value;
            } elseif ($value) {
                $items[] = $key;
            }
        }
        return implode(' ', $items);
    }

    /**
     * Return an attribute `name="value"` from `$name` and `$value` arguments
     *
     * @param array<scalar|null>|scalar|null $value
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
        return $name . '="' . Str::escapeAttr((string) $value) . '"';
    }

    /**
     * Return an attributes string from an array of name and value pairs
     *
     * @param array<string,scalar|null> $data
     */
    public static function attributes(array $data): string
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[] = self::attribute($key, $value);
        }
        return implode(' ', array_filter($attributes));
    }

    /**
     * Return a string containing an HTML tag with specified name, attributes and content
     *
     * @param array<string,scalar|null> $attributes
     *
     * @throws InvalidArgumentException If content is provided for a void element
     */
    public static function tag(string $name, array $attributes = [], ?string ...$content): string
    {
        $name = strtolower($name);
        $attributes = self::attributes($attributes);
        $html = '<' . $name;
        if ($attributes !== '') {
            $html .= ' ' . $attributes;
        }
        $html .= '>';
        if ($content !== []) {
            if (self::isVoid($name)) {
                throw new InvalidArgumentException(sprintf('Cannot set tag content, <%s> is a void element', $name));
            }
            $html .= implode('', $content);
        }
        if (!self::isVoid($name)) {
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
