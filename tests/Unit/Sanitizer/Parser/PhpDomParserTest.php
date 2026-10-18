<?php

namespace Formwork\Tests\Unit\Sanitizer\Parser;

use DOMDocument;
use DOMElement;
use Formwork\Sanitizer\Parser\PhpDomParser;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(PhpDomParser::class)]
final class PhpDomParserTest extends TestCase
{
    public function testWellFormedMarkupIsParsedIntoAFragment(): void
    {
        $fragment = (new PhpDomParser())->parse('<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>');

        $this->assertNotNull($fragment);
        $this->assertInstanceOf(DOMElement::class, $fragment->firstChild);
        $this->assertSame('svg', $fragment->firstChild->nodeName);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedMarkup(): iterable
    {
        yield 'unclosed element' => ['<svg><path>'];
        yield 'mismatched tags' => ['<a><b></a></b>'];
        yield 'unquoted attribute' => ['<svg width=10></svg>'];
        yield 'duplicate attribute' => ['<svg a="1" a="2"></svg>'];
        yield 'undefined entity' => ['<p>&nbsp;</p>'];
        yield 'unescaped ampersand' => ['<p>a & b</p>'];
        yield 'invalid control character' => ["<p>\x01</p>"];
        yield 'xml declaration in the middle' => ['<p/><?xml version="1.0"?>'];
    }

    #[DataProvider('malformedMarkup')]
    public function testMalformedMarkupYieldsNull(string $markup): void
    {
        $this->assertNull((new PhpDomParser())->parse($markup));
    }

    public function testMalformedMarkupDoesNotEmitWarnings(): void
    {
        $this->assertNull((new PhpDomParser())->parse('<svg><unclosed></svg>'));
    }

    public function testEmptyInputIsNotValidMarkup(): void
    {
        $this->assertNull((new PhpDomParser())->parse(''));
    }

    public function testTextOnlyInputIsAccepted(): void
    {
        $fragment = (new PhpDomParser())->parse('just text');

        $this->assertNotNull($fragment);
        $this->assertSame('just text', $fragment->textContent);
    }

    public function testExternalEntitiesAreNeverResolved(): void
    {
        $secret = tempnam(sys_get_temp_dir(), 'formwork-xxe');
        $this->assertNotFalse($secret);
        file_put_contents($secret, 'TOP-SECRET');

        try {
            $phpDomParser = new PhpDomParser();
            $fragment = $phpDomParser->parse('<!DOCTYPE r [<!ENTITY x SYSTEM "file://' . $secret . '">]><r>&x;</r>');
            $output = $fragment === null ? '' : $phpDomParser->serialize($fragment);
        } finally {
            unlink($secret);
        }

        $this->assertStringNotContainsString('TOP-SECRET', $output);
    }

    public function testEntityExpansionBombsAreNotExpanded(): void
    {
        $bomb = '<!DOCTYPE b [<!ENTITY a "aaaaaaaaaa"><!ENTITY b "&a;&a;&a;&a;&a;&a;&a;&a;&a;&a;"><!ENTITY c "&b;&b;&b;&b;&b;&b;&b;&b;&b;&b;">]><r>&c;</r>';
        $phpDomParser = new PhpDomParser();

        $fragment = $phpDomParser->parse($bomb);
        $output = $fragment === null ? '' : $phpDomParser->serialize($fragment);

        $this->assertLessThan(500, strlen($output));
    }

    public function testSerializingAFragmentReturnsItsMarkup(): void
    {
        $phpDomParser = new PhpDomParser();
        $fragment = $phpDomParser->parse('<p class="a">text</p><p>more</p>');

        $this->assertSame('<p class="a">text</p><p>more</p>', $phpDomParser->serialize($fragment));
    }

    public function testSerializingAnElementReturnsOnlyThatElement(): void
    {
        $phpDomParser = new PhpDomParser();
        $fragment = $phpDomParser->parse('<p>one</p><p>two</p>');

        $this->assertNotNull($fragment);
        $this->assertSame('<p>two</p>', $phpDomParser->serialize($fragment->lastChild));
    }

    public function testSerializingADocumentReturnsItsRootElement(): void
    {
        $phpDomParser = new PhpDomParser();
        $document = (new ReflectionProperty($phpDomParser, 'dom'))->getValue($phpDomParser);
        $this->assertInstanceOf(DOMDocument::class, $document);
        $document->loadXML('<root><child/></root>');

        $this->assertSame('<root><child/></root>', $phpDomParser->serialize($document));
    }

    public function testSerializingNothingReturnsAString(): void
    {
        $this->assertIsString((new PhpDomParser())->serialize());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function markupWithProlog(): iterable
    {
        yield 'xml declaration' => ['<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"/>'];
        yield 'xml declaration and newline' => ["<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"/>"];
        yield 'byte order mark' => ["\xEF\xBB\xBF<?xml version=\"1.0\"?><svg xmlns=\"http://www.w3.org/2000/svg\"/>"];
        yield 'public doctype' => ['<?xml version="1.0"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"/>'];
        yield 'comments and processing instructions' => ['<?xml version="1.0"?><!-- Generator: Editor --><?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"/>'];
    }

    #[DataProvider('markupWithProlog')]
    public function testTheDocumentPrologIsRemoved(string $markup): void
    {
        $phpDomParser = new PhpDomParser();
        $fragment = $phpDomParser->parse($markup);

        $this->assertNotNull($fragment);
        $this->assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $phpDomParser->serialize($fragment));
    }

    public function testEntitiesDefinedInTheRemovedDoctypeMakeTheMarkupInvalid(): void
    {
        $this->assertNull((new PhpDomParser())->parse('<!DOCTYPE svg [<!ENTITY x "y">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>'));
    }

    public function testSpecialCharactersAreEscapedWhenSerializing(): void
    {
        $phpDomParser = new PhpDomParser();
        $fragment = $phpDomParser->parse('<p title="&quot;&lt;">&lt;&amp;&gt;</p>');

        $this->assertSame('<p title="&quot;&lt;">&lt;&amp;&gt;</p>', $phpDomParser->serialize($fragment));
    }
}
