<?php

namespace Formwork\Tests\Unit\Sanitizer;

use Formwork\Sanitizer\DomSanitizer;
use Formwork\Sanitizer\SanitizeElementsMethod;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Sanitizer\Fixtures\UriAttributeSanitizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DomSanitizer::class)]
final class DomSanitizerTest extends TestCase
{
    public function testNothingIsAllowedByDefault(): void
    {
        $sanitizer = new DomSanitizer();

        $this->assertSame('', $sanitizer->sanitize('<p class="a">Text</p>'));
    }

    public function testPlainTextIsKept(): void
    {
        $this->assertSame('Plain text', (new DomSanitizer())->sanitize('Plain text'));
    }

    public function testEmptyStringIsSanitizedToAnEmptyString(): void
    {
        $this->assertSame('', (new DomSanitizer())->sanitize(''));
    }

    public function testConfigurationMethodsAreFluent(): void
    {
        $sanitizer = new DomSanitizer();

        $this->assertSame($sanitizer, $sanitizer->allowedElements(['p']));
        $this->assertSame($sanitizer, $sanitizer->allowedAttributes(['title']));
        $this->assertSame($sanitizer, $sanitizer->allowedUriSchemes(['https']));
        $this->assertSame($sanitizer, $sanitizer->disallowExternalUris());
        $this->assertSame($sanitizer, $sanitizer->sanitizeElementsMethod(SanitizeElementsMethod::Escape));
    }

    public function testOnlyAllowedElementsAndAttributesAreKept(): void
    {
        $sanitizer = (new DomSanitizer())
            ->allowedElements(['p', 'b'])
            ->allowedAttributes(['title']);

        $html = $sanitizer->sanitize('<p title="Title" class="a" onclick="x()">Hello <b id="x">world</b><i>removed</i></p>');

        $this->assertSame('<p title="Title">Hello <b>world</b></p>', $html);
    }

    public function testAllowedListsAreReplacedAndNotMerged(): void
    {
        $sanitizer = (new DomSanitizer())->allowedElements(['p'])->allowedElements(['div']);

        $this->assertSame('<div>kept</div>', $sanitizer->sanitize('<p>removed</p><div>kept</div>'));
    }

    public function testDisallowedElementsAreRemovedWithTheirContent(): void
    {
        $sanitizer = (new DomSanitizer())->allowedElements(['p']);

        $this->assertSame('<p>kept</p>', $sanitizer->sanitize('<p>kept</p><script>alert(1)</script><div><p>nested</p></div>'));
    }

    public function testDisallowedElementsCanBeEscapedInsteadOfRemoved(): void
    {
        $sanitizer = (new DomSanitizer())
            ->allowedElements(['p'])
            ->sanitizeElementsMethod(SanitizeElementsMethod::Escape);

        $html = $sanitizer->sanitize('<p>kept</p><script>alert(1)</script>');

        $this->assertSame('<p>kept</p>&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testChildrenOfAllowedElementsAreSanitizedRecursively(): void
    {
        $sanitizer = (new DomSanitizer())
            ->allowedElements(['div', 'p', 'span'])
            ->allowedAttributes(['title']);

        $html = $sanitizer->sanitize('<div onclick="x()"><p><span title="t" style="x"><script>alert(1)</script>ok</span></p></div>');

        $this->assertSame('<div><p><span title="t">ok</span></p></div>', $html);
    }

    public function testAttributesOfEscapedElementsAreNotRenderedAsMarkup(): void
    {
        $sanitizer = (new DomSanitizer())
            ->allowedElements(['p'])
            ->sanitizeElementsMethod(SanitizeElementsMethod::Escape);

        $html = $sanitizer->sanitize('<p><img src=x onerror=alert(1)></p>');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testInvalidUnicodeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new DomSanitizer())->sanitize("<p>\xff</p>");
    }

    public function testUriAttributesAreNotCheckedUnlessDeclared(): void
    {
        $sanitizer = (new DomSanitizer())->allowedElements(['a'])->allowedAttributes(['href']);

        $this->assertSame('<a href="javascript:alert(1)">x</a>', $sanitizer->sanitize('<a href="javascript:alert(1)">x</a>'));
    }

    #[DataProvider('safeUriProvider')]
    public function testSafeUrisAreKept(string $uri): void
    {
        $html = $this->uriSanitizer()->sanitize('<a href="' . $uri . '">x</a>');

        $this->assertSame('<a href="' . $uri . '">x</a>', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safeUriProvider(): iterable
    {
        yield 'https' => ['https://example.com/path?query=1#fragment'];
        yield 'http' => ['http://example.com'];
        yield 'uppercase scheme' => ['HTTPS://example.com'];
        yield 'absolute path' => ['/path/to/page'];
        yield 'relative path' => ['../page.html'];
        yield 'query only' => ['?page=2'];
        yield 'fragment only' => ['#section'];
        yield 'empty' => [''];
    }

    #[DataProvider('unsafeUriProvider')]
    public function testUnsafeUrisAreRemoved(string $uri): void
    {
        $html = $this->uriSanitizer()->sanitize('<a href="' . $uri . '">x</a>');

        $this->assertSame('<a>x</a>', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeUriProvider(): iterable
    {
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'uppercase javascript' => ['JAVASCRIPT:alert(1)'];
        yield 'mixed case javascript' => ['JaVaScRiPt:alert(1)'];
        yield 'leading space' => [' javascript:alert(1)'];
        yield 'leading newline' => ["\njavascript:alert(1)"];
        yield 'embedded tab' => ["java\tscript:alert(1)"];
        yield 'embedded newline' => ["java\nscript:alert(1)"];
        yield 'decimal entity' => ['&#106;avascript:alert(1)'];
        yield 'hexadecimal entity' => ['&#x6A;avascript:alert(1)'];
        yield 'entity without semicolon' => ['&#106avascript:alert(1)'];
        yield 'entity for the colon' => ['javascript&#58;alert(1)'];
        yield 'named entity for the colon' => ['javascript&colon;alert(1)'];
        yield 'named entity for the tab' => ['jav&Tab;ascript:alert(1)'];
        yield 'percent encoding' => ['%6Aavascript:alert(1)'];
        yield 'data' => ['data:text/html,<script>alert(1)</script>'];
        yield 'base64 data' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='];
        yield 'vbscript' => ['vbscript:msgbox(1)'];
        yield 'file' => ['file:///nonexistent/secret'];
        yield 'ftp' => ['ftp://example.com/file'];
        yield 'protocol relative' => ['//evil.test/path'];
    }

    public function testExternalUrisCanBeDisallowed(): void
    {
        $sanitizer = $this->uriSanitizer()->disallowExternalUris();

        $this->assertSame(
            '<a>external</a><a href="/local">local</a>',
            $sanitizer->sanitize('<a href="https://example.com">external</a><a href="/local">local</a>')
        );
    }

    public function testAllowedUriSchemesCanBeCustomized(): void
    {
        $sanitizer = $this->uriSanitizer()->allowedUriSchemes(['ftp']);

        $this->assertSame(
            '<a href="ftp://example.com/file">ftp</a><a>https</a>',
            $sanitizer->sanitize('<a href="ftp://example.com/file">ftp</a><a href="https://example.com">https</a>')
        );
    }

    public function testEveryDeclaredUriAttributeIsChecked(): void
    {
        $sanitizer = (new UriAttributeSanitizer())
            ->allowedElements(['a', 'img'])
            ->allowedAttributes(['href', 'src', 'title']);

        $html = $sanitizer->sanitize('<a href="javascript:alert(1)" title="t">x</a><img src="javascript:alert(2)" title="i">');

        $this->assertSame('<a title="t">x</a><img title="i">', $html);
    }

    private function uriSanitizer(): UriAttributeSanitizer
    {
        return (new UriAttributeSanitizer())
            ->allowedElements(['a'])
            ->allowedAttributes(['href']);
    }
}
