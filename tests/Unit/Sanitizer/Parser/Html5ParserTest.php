<?php

namespace Formwork\Tests\Unit\Sanitizer\Parser;

use DOMElement;
use Formwork\Sanitizer\Parser\Html5Parser;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Html5Parser::class)]
final class Html5ParserTest extends TestCase
{
    public function testFragmentIsParsed(): void
    {
        $fragment = (new Html5Parser())->parse('<p>Hello <b>world</b></p>');

        $this->assertNotNull($fragment);
        $this->assertInstanceOf(DOMElement::class, $fragment->firstChild);
        $this->assertSame('p', $fragment->firstChild->nodeName);
        $this->assertSame('Hello world', $fragment->textContent);
    }

    public function testUnclosedScriptContentIsKeptAsText(): void
    {
        $html5Parser = new Html5Parser();

        $fragment = $html5Parser->parse('<script>alert(1)');

        $this->assertNotNull($fragment);
        $this->assertSame('script', $fragment->firstChild?->nodeName);
        $this->assertSame('alert(1)', $fragment->firstChild->textContent);
    }

    public function testEmptyInputProducesAnEmptyFragment(): void
    {
        $fragment = (new Html5Parser())->parse('');

        $this->assertNotNull($fragment);
        $this->assertFalse($fragment->hasChildNodes());
    }

    public function testSerializationRoundTrip(): void
    {
        $html5Parser = new Html5Parser();
        $html = '<div class="a b" data-x="1"><img src="x.png" alt="x"><br>text &amp; more</div>';

        $this->assertSame($html, $html5Parser->serialize($html5Parser->parse($html)));
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $html5Parser = new Html5Parser();
        $fragment = $html5Parser->parse('<a title="&quot;&lt;&amp;&gt;" href="/x?a=1&amp;b=2">link</a>');

        $serialized = $html5Parser->serialize($fragment);

        $this->assertStringContainsString('title="&quot;<&amp;>"', $serialized);
        $this->assertStringContainsString('href="/x?a=1&amp;b=2"', $serialized);
    }

    public function testSerializingAnElementReturnsOnlyThatElement(): void
    {
        $html5Parser = new Html5Parser();
        $fragment = $html5Parser->parse('<p>one</p><p>two</p>');

        $this->assertNotNull($fragment);
        $this->assertSame('<p>one</p>', $html5Parser->serialize($fragment->firstChild));
    }

    public function testNestingIsDeepEnoughToNotCrashTheParser(): void
    {
        $html5Parser = new Html5Parser();

        $fragment = $html5Parser->parse(str_repeat('<div>', 2000) . 'deep' . str_repeat('</div>', 2000));

        $this->assertNotNull($fragment);
        $this->assertSame('deep', $fragment->textContent);
    }

    public function testNonUtf8InputDoesNotCrash(): void
    {
        $fragment = (new Html5Parser())->parse("<p>caf\xE9</p>");

        $this->assertNotNull($fragment);
    }
}
