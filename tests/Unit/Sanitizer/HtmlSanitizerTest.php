<?php

namespace Formwork\Tests\Unit\Sanitizer;

use Formwork\Sanitizer\HtmlSanitizer;
use Formwork\Sanitizer\Reference\HtmlReference;
use Formwork\Sanitizer\SanitizeElementsMethod;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(HtmlSanitizer::class)]
final class HtmlSanitizerTest extends TestCase
{
    #[DataProvider('safeMarkupProvider')]
    public function testSafeMarkupIsPreserved(string $markup): void
    {
        $this->assertSame($markup, (new HtmlSanitizer())->sanitize($markup));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safeMarkupProvider(): iterable
    {
        yield 'text' => ['Plain text'];
        yield 'formatting' => ['<p>Hello <b>world</b> and <em>everyone</em></p>'];
        yield 'link' => ['<a href="https://example.com/a?b=1" target="_blank" rel="noopener">link</a>'];
        yield 'relative link' => ['<a href="/relative/path#section">link</a>'];
        yield 'mail link' => ['<a href="mailto:user@example.com">mail</a>'];
        yield 'image' => ['<img src="/image.png" alt="Alt">'];
        yield 'image with srcset' => ['<img src="a.png" srcset="a.png 1x, b.png 2x">'];
        yield 'list' => ['<ul><li>One</li><li>Two</li></ul>'];
        yield 'table' => ['<table><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'];
        yield 'escaped text' => ['<p>1 &lt; 2 &amp;&amp; 3 &gt; 2</p>'];
        yield 'form control' => ['<form><button formaction="https://example.com/submit">Go</button></form>'];
        yield 'meta refresh without URL' => ['<meta http-equiv="refresh" content="5">'];
        yield 'meta refresh to a safe URL' => ['<meta http-equiv="refresh" content="5;url=https://example.com">'];
    }

    #[DataProvider('dangerousElementProvider')]
    public function testDangerousElementsAreRemoved(string $markup, string $expected): void
    {
        $this->assertSame($expected, (new HtmlSanitizer())->sanitize($markup));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dangerousElementProvider(): iterable
    {
        yield 'script' => ['<p>a</p><script>alert(1)</script>', '<p>a</p>'];
        yield 'style' => ['<style>p { display: none }</style><p>a</p>', '<p>a</p>'];
        yield 'iframe' => ['<iframe src="https://example.com"></iframe><p>a</p>', '<p>a</p>'];
        yield 'object' => ['<object data="x"></object><p>a</p>', '<p>a</p>'];
        yield 'embed' => ['<embed src="x"><p>a</p>', '<p>a</p>'];
        yield 'base' => ['<base href="https://evil.test/"><p>a</p>', '<p>a</p>'];
        yield 'unknown element' => ['<div><custom-element>a</custom-element></div>', '<div></div>'];
        yield 'mathml' => ['<math><mtext><style><img src=x onerror=alert(1)></style></mtext></math>', ''];
        yield 'noscript breakout' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>">', ''];
    }

    #[DataProvider('eventHandlerProvider')]
    public function testEventHandlersAreRemoved(string $attribute): void
    {
        $html = (new HtmlSanitizer())->sanitize('<p ' . $attribute . '="alert(1)" title="kept">text</p>');

        $this->assertSame('<p title="kept">text</p>', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function eventHandlerProvider(): iterable
    {
        yield 'onclick' => ['onclick'];
        yield 'onerror' => ['onerror'];
        yield 'onload' => ['onload'];
        yield 'onmouseover' => ['onmouseover'];
        yield 'onfocus' => ['onfocus'];
        yield 'uppercase' => ['ONCLICK'];
    }

    #[DataProvider('uriAttributeProvider')]
    public function testEveryUriAttributeIsValidated(string $attribute): void
    {
        $html = (new HtmlSanitizer())->sanitize('<a ' . $attribute . '="javascript:alert(1)">text</a>');

        $this->assertSame('<a>text</a>', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function uriAttributeProvider(): iterable
    {
        foreach (HtmlReference::URI_ATTRIBUTES as $attribute) {
            yield $attribute => [$attribute];
        }
    }

    /**
     * Attributes taking a URL must be validated as URIs when they are allowed
     */
    #[DataProvider('urlAttributeProvider')]
    public function testUrlValuedAttributesAreNeverAllowedWithoutUriValidation(string $attribute): void
    {
        if (!in_array($attribute, HtmlReference::ALLOWED_ATTRIBUTES, true)) {
            $this->assertNotContains($attribute, HtmlReference::URI_ATTRIBUTES, 'Disallowed attributes need no validation');
            return;
        }

        $this->assertContains($attribute, HtmlReference::URI_ATTRIBUTES);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function urlAttributeProvider(): iterable
    {
        $attributes = [
            'action', 'archive', 'background', 'cite', 'classid', 'codebase', 'data', 'dynsrc', 'formaction',
            'href', 'icon', 'imagesrcset', 'itemid', 'itemtype', 'longdesc', 'lowsrc', 'manifest', 'ping',
            'poster', 'profile', 'src', 'srcset', 'usemap',
        ];

        foreach ($attributes as $attribute) {
            yield $attribute => [$attribute];
        }
    }

    public function testFormactionWithAScriptUriIsRemoved(): void
    {
        $html = (new HtmlSanitizer())->sanitize('<form><button formaction="javascript:alert(document.domain)">Click</button></form>');

        $this->assertSame('<form><button>Click</button></form>', $html);
    }

    #[DataProvider('formactionProvider')]
    public function testFormactionIsValidatedOnEveryControl(string $markup, string $expected): void
    {
        $this->assertSame($expected, (new HtmlSanitizer())->sanitize($markup));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function formactionProvider(): iterable
    {
        yield 'input submit' => ['<input type="submit" formaction="javascript:alert(1)">', '<input type="submit">'];
        yield 'input image' => ['<input type="image" formaction="javascript:alert(1)">', '<input type="image">'];
        yield 'mixed case' => ['<button formaction="JaVaScRiPt:alert(1)">x</button>', '<button>x</button>'];
        yield 'leading whitespace' => ['<button formaction=" javascript:alert(1)">x</button>', '<button>x</button>'];
        yield 'encoded scheme' => ['<button formaction="&#x6A;avascript:alert(1)">x</button>', '<button>x</button>'];
        yield 'data URI' => ['<button formaction="data:text/html,<script>alert(1)</script>">x</button>', '<button>x</button>'];
        yield 'safe URL' => ['<button formaction="https://example.com/ok">x</button>', '<button formaction="https://example.com/ok">x</button>'];
        yield 'relative URL' => ['<button formaction="/submit">x</button>', '<button formaction="/submit">x</button>'];
    }

    #[DataProvider('srcsetProvider')]
    public function testSrcsetCandidatesAreAllValidated(string $attribute, string $value, bool $kept): void
    {
        $html = (new HtmlSanitizer())->sanitize('<img ' . $attribute . '="' . $value . '">');

        if ($kept) {
            $this->assertStringContainsString($attribute . '="' . $value . '"', $html);
        } else {
            $this->assertSame('<img>', $html);
        }
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function srcsetProvider(): iterable
    {
        yield 'single safe candidate' => ['srcset', 'a.png', true];
        yield 'safe candidates with descriptors' => ['srcset', 'a.png 1x, b.png 2x', true];
        yield 'safe candidates with widths' => ['srcset', 'small.png 320w, large.png 960w', true];
        yield 'imagesrcset' => ['imagesrcset', 'a.png 1x, b.png 2x', true];
        yield 'single unsafe candidate' => ['srcset', 'javascript:alert(1)', false];
        yield 'unsafe candidate with descriptor' => ['srcset', 'javascript:alert(1) 1x', false];
        yield 'unsafe first candidate' => ['srcset', 'javascript:alert(1) 1x, ok.png 2x', false];
        yield 'unsafe last candidate' => ['srcset', 'ok.png 1x, javascript:alert(1) 2x', false];
        yield 'unsafe candidate without space after the comma' => ['srcset', 'ok.png 1x,javascript:alert(1) 2x', false];
        yield 'unsafe candidate with surrounding spaces' => ['srcset', '  ok.png  ,  javascript:alert(1)  ', false];
        yield 'encoded unsafe candidate' => ['srcset', 'java&#115;cript:alert(1) 1x', false];
        yield 'unsafe imagesrcset' => ['imagesrcset', 'ok.png 1x, javascript:alert(1) 2x', false];
        yield 'data URI candidate' => ['srcset', 'data:text/html;base64,PHNjcmlwdD4= 1x', false];
    }

    #[DataProvider('metaRefreshProvider')]
    public function testMetaRefreshWithUnsafeUrisIsRemoved(string $markup, string $expected): void
    {
        $this->assertSame($expected, (new HtmlSanitizer())->sanitize($markup));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function metaRefreshProvider(): iterable
    {
        yield 'url prefix' => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'uppercase url prefix' => ['<meta http-equiv="refresh" content="0; URL=javascript:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'comma separator' => ['<meta http-equiv="refresh" content="0,url=javascript:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'surrounding spaces' => ['<meta http-equiv="refresh" content="  0 ; url=JaVaScRiPt:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'encoded scheme' => ['<meta http-equiv="refresh" content="0;url=&#106;avascript:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'data URI' => ['<meta http-equiv="refresh" content="0;url=data:text/html,x">', '<meta http-equiv="refresh">'];
        yield 'without url prefix' => ['<meta http-equiv="refresh" content="0;javascript:alert(1)">', '<meta http-equiv="refresh">'];
        yield 'uppercase http-equiv' => ['<meta http-equiv="REFRESH" content="0;url=javascript:alert(1)">', '<meta http-equiv="REFRESH">'];
        yield 'quoted URL' => ['<meta http-equiv="refresh" content="0;url=\'javascript:alert(1)\'">', '<meta http-equiv="refresh">'];
        yield 'spaces around the equal sign' => ['<meta http-equiv="refresh" content="0;url = javascript:alert(1)">', '<meta http-equiv="refresh">'];
    }

    public function testOtherMetaContentIsNotAffected(): void
    {
        $markup = '<meta name="description" content="0;url=javascript:alert(1)">';

        $this->assertSame($markup, (new HtmlSanitizer())->sanitize($markup));
    }

    public function testUnsafeSchemesAreRemovedFromLinks(): void
    {
        $html = (new HtmlSanitizer())->sanitize(
            '<a href="javascript:alert(1)">a</a><a href="data:text/html,x">b</a><a href="//evil.test">c</a><a href="mailto:me@example.com">d</a>'
        );

        $this->assertSame('<a>a</a><a>b</a><a>c</a><a href="mailto:me@example.com">d</a>', $html);
    }

    public function testExternalUrisCanBeDisallowed(): void
    {
        $sanitizer = (new HtmlSanitizer())->disallowExternalUris();

        $this->assertSame(
            '<a>external</a><a href="/local">local</a><img>',
            $sanitizer->sanitize('<a href="https://example.com">external</a><a href="/local">local</a><img src="https://example.com/x.png">')
        );
    }

    public function testDisallowedElementsCanBeEscaped(): void
    {
        $sanitizer = (new HtmlSanitizer())->sanitizeElementsMethod(SanitizeElementsMethod::Escape);

        $html = $sanitizer->sanitize('<p>a</p><script>alert(1)</script>');

        $this->assertSame('<p>a</p>&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testEmbeddedSvgIsSanitizedWithTheSvgRules(): void
    {
        $html = (new HtmlSanitizer())->sanitize(
            '<svg onload="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)"><text>x</text></a><circle r="5"/></svg>'
        );

        $this->assertStringContainsString('<circle', $html);
        $this->assertStringNotContainsString('onload', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function testSmilAnimationsCannotSetUriAttributesOfEmbeddedSvg(): void
    {
        $html = (new HtmlSanitizer())->sanitize(
            '<svg><a><animate attributeName="href" values="javascript:alert(1)"/><text>x</text></a></svg>'
        );

        $this->assertStringNotContainsString('attributeName', $html);
    }

    public function testInvalidUnicodeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new HtmlSanitizer())->sanitize("<p>\xff</p>");
    }

    public function testEmptyStringIsAccepted(): void
    {
        $this->assertSame('', (new HtmlSanitizer())->sanitize(''));
    }
}
