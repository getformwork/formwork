<?php

namespace Formwork\Tests\Unit\Sanitizer\Reference;

use Formwork\Sanitizer\Reference\HtmlReference;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Policy invariants for the HTML allowlists: whatever else changes, these must always hold
 */
#[CoversClass(HtmlReference::class)]
final class HtmlReferenceTest extends TestCase
{
    /**
     * Elements able to run code, load arbitrary documents or change the base of the page
     */
    private const array FORBIDDEN_ELEMENTS = ['script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'base', 'noscript', 'noembed', 'noframes', 'style', 'math', 'foreignobject'];

    /**
     * Attributes that carry URLs and therefore must be validated as such
     */
    private const array URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster', 'data', 'cite', 'background', 'longdesc', 'manifest', 'ping', 'usemap', 'lowsrc', 'archive', 'codebase', 'classid', 'srcset', 'imagesrcset', 'dynsrc', 'profile', 'icon'];

    public function testListsContainOnlyLowercaseUniqueNames(): void
    {
        foreach ([HtmlReference::ALLOWED_ELEMENTS, HtmlReference::ALLOWED_ATTRIBUTES, HtmlReference::URI_ATTRIBUTES] as $list) {
            $this->assertSame(array_values(array_unique($list)), $list, 'Duplicated entries');
            foreach ($list as $name) {
                $this->assertSame(strtolower($name), $name);
                $this->assertMatchesRegularExpression('/^[a-z][a-z0-9-]*$/', $name);
            }
        }
    }

    public function testNoElementAbleToRunCodeIsAllowed(): void
    {
        $this->assertSame([], array_values(array_intersect(self::FORBIDDEN_ELEMENTS, HtmlReference::ALLOWED_ELEMENTS)));
    }

    public function testNoEventHandlerAttributeIsAllowed(): void
    {
        foreach (HtmlReference::ALLOWED_ATTRIBUTES as $attribute) {
            $this->assertStringStartsNotWith('on', $attribute, sprintf('"%s" looks like an event handler', $attribute));
        }
    }

    public function testEveryAllowedUrlAttributeIsTreatedAsUri(): void
    {
        $missing = array_diff(array_intersect(self::URL_ATTRIBUTES, HtmlReference::ALLOWED_ATTRIBUTES), HtmlReference::URI_ATTRIBUTES);

        $this->assertSame([], array_values($missing), 'URL attributes allowed but not validated as URIs');
    }

    public function testUriAttributesAreAllAllowedAttributes(): void
    {
        $this->assertSame([], array_values(array_diff(HtmlReference::URI_ATTRIBUTES, HtmlReference::ALLOWED_ATTRIBUTES)));
    }

    public function testCoreStructuralElementsAreAllowed(): void
    {
        foreach (['p', 'a', 'img', 'ul', 'ol', 'li', 'table', 'h1', 'blockquote', 'pre', 'code'] as $element) {
            $this->assertContains($element, HtmlReference::ALLOWED_ELEMENTS);
        }
    }
}
