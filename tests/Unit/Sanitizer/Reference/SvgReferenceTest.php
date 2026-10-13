<?php

namespace Formwork\Tests\Unit\Sanitizer\Reference;

use Formwork\Sanitizer\Reference\SvgReference;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Policy invariants for the SVG allowlists: whatever else changes, these must always hold
 */
#[CoversClass(SvgReference::class)]
final class SvgReferenceTest extends TestCase
{
    private const array FORBIDDEN_ELEMENTS = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'applet', 'handler', 'listener', 'audio', 'video', 'canvas'];

    public function testNamespaceIsTheSvgOne(): void
    {
        $this->assertSame('http://www.w3.org/2000/svg', SvgReference::NAMESPACE_URI);
    }

    public function testListsContainNoDuplicates(): void
    {
        foreach ([SvgReference::ALLOWED_ELEMENTS, SvgReference::ALLOWED_ATTRIBUTES, SvgReference::URI_ATTRIBUTES, SvgReference::SMIL_ELEMENTS] as $list) {
            $this->assertSame(array_values(array_unique($list)), $list);
        }
    }

    public function testNoElementAbleToRunCodeIsAllowed(): void
    {
        $allowed = array_map('strtolower', SvgReference::ALLOWED_ELEMENTS);

        $this->assertSame([], array_values(array_intersect(self::FORBIDDEN_ELEMENTS, $allowed)));
    }

    public function testNoEventHandlerAttributeIsAllowed(): void
    {
        foreach (SvgReference::ALLOWED_ATTRIBUTES as $attribute) {
            $this->assertStringStartsNotWith('on', strtolower($attribute), sprintf('"%s" looks like an event handler', $attribute));
        }
    }

    public function testSmilElementsAreAllowedElements(): void
    {
        $this->assertSame([], array_values(array_diff(SvgReference::SMIL_ELEMENTS, SvgReference::ALLOWED_ELEMENTS)));
    }

    public function testBothHrefFormsAreValidatedAsUris(): void
    {
        $this->assertContains('href', SvgReference::URI_ATTRIBUTES);
        $this->assertContains('xlink:href', SvgReference::URI_ATTRIBUTES);
    }

    public function testAnimationTargetsCanOnlyBeUsedByValidatedElements(): void
    {
        // SMIL elements can rewrite href at runtime through attributeName, so they must be known to the sanitizer
        foreach (['animate', 'set', 'animateTransform', 'animateMotion', 'discard'] as $element) {
            $this->assertContains($element, SvgReference::SMIL_ELEMENTS);
        }
    }
}
