<?php

namespace Formwork\Tests\Unit\Sanitizer;

use Formwork\Sanitizer\Reference\SvgReference;
use Formwork\Sanitizer\SvgSanitizer;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(SvgSanitizer::class)]
final class SvgSanitizerTest extends TestCase
{
    private const string NAMESPACE_ATTRIBUTE = 'xmlns="http://www.w3.org/2000/svg"';

    public function testSafeDocumentIsPreserved(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M0 0h16v16H0z" fill="#000"/></svg>';

        $this->assertSame($svg, (new SvgSanitizer())->sanitize($svg));
    }

    public function testSvgNamespaceIsAddedWhenMissing(): void
    {
        $svg = (new SvgSanitizer())->sanitize('<svg><circle r="5"/></svg>');

        $this->assertSame('<svg ' . self::NAMESPACE_ATTRIBUTE . '><circle r="5"/></svg>', $svg);
    }

    public function testNamespaceIsNotDuplicated(): void
    {
        $svg = (new SvgSanitizer())->sanitize('<svg xmlns="http://www.w3.org/2000/svg"><circle r="5"/></svg>');

        $this->assertSame(1, substr_count($svg, 'xmlns='));
    }

    public function testAttributesAreKeptWhenTheNamespaceIsAdded(): void
    {
        $svg = (new SvgSanitizer())->sanitize('<svg width="10" height="20" viewBox="0 0 10 20"><circle r="5"/></svg>');

        $this->assertStringContainsString('width="10"', $svg);
        $this->assertStringContainsString('height="20"', $svg);
        $this->assertStringContainsString('viewBox="0 0 10 20"', $svg);
    }

    public function testXlinkNamespaceDeclarationIsPreserved(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/></svg>';

        $this->assertSame($svg, (new SvgSanitizer())->sanitize($svg));
    }

    public function testEntitiesInTextAreKept(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>a &amp; b &lt;c&gt;</text></svg>';

        $this->assertSame($svg, (new SvgSanitizer())->sanitize($svg));
    }

    #[DataProvider('dangerousMarkupProvider')]
    public function testDangerousMarkupIsRemoved(string $input, string $expectedFragment): void
    {
        $svg = (new SvgSanitizer())->sanitize($input);

        $this->assertStringContainsString('<svg ' . self::NAMESPACE_ATTRIBUTE, $svg);
        $this->assertStringContainsString($expectedFragment, $svg);
        $this->assertStringNotContainsString('alert', $svg);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dangerousMarkupProvider(): iterable
    {
        yield 'script element' => ['<svg><script>alert(1)</script><circle r="5"/></svg>', '<circle r="5"/>'];
        yield 'onload handler' => ['<svg onload="alert(1)"><circle r="5"/></svg>', '<circle r="5"/>'];
        yield 'onclick handler' => ['<svg><circle r="5" onclick="alert(1)"/></svg>', '<circle r="5"/>'];
        yield 'foreignObject' => ['<svg><foreignObject><p onclick="alert(1)">x</p></foreignObject><circle r="5"/></svg>', '<circle r="5"/>'];
        yield 'javascript href' => ['<svg><a href="javascript:alert(1)"><text>x</text></a></svg>', '<a><text>x</text></a>'];
        yield 'javascript xlink:href' => ['<svg><a xlink:href="javascript:alert(1)"><text>x</text></a></svg>', '<a><text>x</text></a>'];
        yield 'encoded javascript href' => ['<svg><a href="&#106;avascript:alert(1)"><text>x</text></a></svg>', '<a><text>x</text></a>'];
        yield 'data URI image' => ['<svg><image href="data:text/html,alert(1)"/></svg>', '<image/>'];
    }

    public function testSafeLinksAreKept(): void
    {
        $svg = (new SvgSanitizer())->sanitize('<svg><a href="https://example.com/page"><text>x</text></a></svg>');

        $this->assertStringContainsString('<a href="https://example.com/page">', $svg);
    }

    #[DataProvider('unsafeSmilAttributeNameProvider')]
    public function testSmilAnimationsCannotTargetUriOrUnknownAttributes(string $element, string $attributeName): void
    {
        $svg = (new SvgSanitizer())->sanitize(
            '<svg><a><' . $element . ' attributeName="' . $attributeName . '" to="javascript:alert(1)" values="javascript:alert(1)"/><text>x</text></a></svg>'
        );

        $this->assertStringNotContainsString('attributeName', $svg);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeSmilAttributeNameProvider(): iterable
    {
        foreach (['animate', 'set'] as $element) {
            yield "$element href" => [$element, 'href'];
            yield "$element xlink:href" => [$element, 'xlink:href'];
            yield "$element src" => [$element, 'src'];
            yield "$element uppercase href" => [$element, 'HREF'];
            yield "$element href with leading space" => [$element, ' href'];
            yield "$element href with trailing space" => [$element, 'href '];
            yield "$element event handler" => [$element, 'onclick'];
            yield "$element unknown attribute" => [$element, 'unknown'];
            yield "$element empty attribute" => [$element, ''];
        }

        yield 'animateTransform href' => ['animateTransform', 'href'];
        yield 'animateMotion href' => ['animateMotion', 'href'];
    }

    #[DataProvider('safeSmilAttributeNameProvider')]
    public function testSmilAnimationsOfSafeAttributesAreKept(string $element, string $attributeName): void
    {
        $svg = (new SvgSanitizer())->sanitize(
            '<svg><circle><' . $element . ' attributeName="' . $attributeName . '" from="1" to="5" dur="1s"/></circle></svg>'
        );

        $this->assertStringContainsString('attributeName="' . $attributeName . '"', $svg);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function safeSmilAttributeNameProvider(): iterable
    {
        yield 'animate r' => ['animate', 'r'];
        yield 'animate opacity' => ['animate', 'opacity'];
        yield 'set fill' => ['set', 'fill'];
        yield 'animateTransform transform' => ['animateTransform', 'transform'];
    }

    public function testAttributeNameOutsideSmilElementsIsNotTreatedAsSmil(): void
    {
        $svg = (new SvgSanitizer())->sanitize('<svg><circle attributeName="href" r="5"/></svg>');

        $this->assertStringNotContainsString('javascript', $svg);
        $this->assertStringContainsString('r="5"', $svg);
    }

    public function testSmilElementsDoNotInheritSafeAttributeNamesFromSiblings(): void
    {
        $svg = (new SvgSanitizer())->sanitize(
            '<svg><circle><animate attributeName="r" to="5"/><animate attributeName="href" to="javascript:alert(1)"/></circle></svg>'
        );

        $this->assertSame(1, substr_count($svg, 'attributeName'));
        $this->assertStringContainsString('attributeName="r"', $svg);
    }

    public function testEveryUriAttributeOfTheReferenceIsRejectedAsSmilTarget(): void
    {
        foreach (SvgReference::URI_ATTRIBUTES as $attribute) {
            $svg = (new SvgSanitizer())->sanitize('<svg><a><set attributeName="' . $attribute . '" to="javascript:alert(1)"/></a></svg>');

            $this->assertStringNotContainsString('attributeName', $svg, $attribute);
        }
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidDocumentProvider')]
    public function testInvalidDocumentsAreRejected(string $input, string $exception = UnexpectedValueException::class): void
    {
        $this->expectException($exception);
        (new SvgSanitizer())->sanitize($input);
    }

    /**
     * @return iterable<string, array{string}|array{string, class-string<\Throwable>}>
     */
    public static function invalidDocumentProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'plain text' => ['not an svg'];
        yield 'other root element' => ['<div>x</div>'];
        yield 'multiple roots' => ['<svg></svg><svg></svg>'];
        yield 'root followed by other element' => ['<svg></svg><p>x</p>'];
        yield 'malformed markup' => ['<svg><circle></svg>'];
        yield 'external entity' => ['<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///nonexistent/secret">]><svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>'];
        yield 'undefined entity' => ['<svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>'];
        yield 'invalid unicode' => ["<svg><text>\xff</text></svg>", InvalidArgumentException::class];
    }
}
