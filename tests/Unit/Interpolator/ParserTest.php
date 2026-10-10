<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Errors\SyntaxError;
use Formwork\Interpolator\Nodes\AbstractNode;
use Formwork\Interpolator\Nodes\ArgumentsNode;
use Formwork\Interpolator\Nodes\ArrayNode;
use Formwork\Interpolator\Nodes\IdentifierNode;
use Formwork\Interpolator\Nodes\ImplicitArrayKeyNode;
use Formwork\Interpolator\Nodes\NumberNode;
use Formwork\Interpolator\Nodes\StringNode;
use Formwork\Interpolator\Parser;
use Formwork\Interpolator\ParserInterface;
use Formwork\Interpolator\Tokenizer;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
    public function testParserImplementsItsInterface(): void
    {
        $this->assertInstanceOf(ParserInterface::class, new Parser(Tokenizer::tokenizeString('a')));
    }

    public function testParseTokenStreamIsEquivalentToParse(): void
    {
        $this->assertEquals(
            (new Parser(Tokenizer::tokenizeString('a.b(1)')))->parse(),
            Parser::parseTokenStream(Tokenizer::tokenizeString('a.b(1)')),
        );
    }

    public function testSingleIdentifier(): void
    {
        $node = $this->parse('name');

        $this->assertInstanceOf(IdentifierNode::class, $node);
        $this->assertSame('name', $node->value());
        $this->assertNull($node->arguments());
        $this->assertNull($node->traverse());
    }

    public function testDotNotationBuildsANestedIdentifierChain(): void
    {
        $node = $this->parse('a.b.c');

        $this->assertSame('a', $node->value());
        $this->assertSame('b', $node->traverse()->value());
        $this->assertSame('c', $node->traverse()->traverse()->value());
        $this->assertNull($node->traverse()->traverse()->traverse());
    }

    public function testBracketsNotationWithNumbers(): void
    {
        $node = $this->parse('a[3]');

        $this->assertInstanceOf(NumberNode::class, $node->traverse());
        $this->assertSame(3, $node->traverse()->value());
    }

    public function testBracketsNotationWithStrings(): void
    {
        $node = $this->parse('a["key"]');

        $this->assertInstanceOf(StringNode::class, $node->traverse());
        $this->assertSame('key', $node->traverse()->value());
    }

    public function testCallWithoutArguments(): void
    {
        $node = $this->parse('f()');

        $this->assertInstanceOf(ArgumentsNode::class, $node->arguments());
        $this->assertSame([], $node->arguments()->value());
    }

    public function testCallWithArgumentsOfEveryKind(): void
    {
        $node = $this->parse('f(1, "two", three, [4], g(5))');

        $types = array_map(static fn(AbstractNode $argument): string => $argument->type(), $node->arguments()->value());

        $this->assertSame(['number', 'string', 'identifier', 'array', 'identifier'], $types);
    }

    public function testCallFollowedByTraversal(): void
    {
        $node = $this->parse('f(1).g(2)');

        $this->assertSame('f', $node->value());
        $this->assertCount(1, $node->arguments()->value());
        $this->assertSame('g', $node->traverse()->value());
        $this->assertCount(1, $node->traverse()->arguments()->value());
    }

    public function testNumbersAreConvertedToIntegersOrFloats(): void
    {
        $values = array_map(
            static fn(AbstractNode $argument): mixed => $argument->value(),
            $this->parse('f(1, 1.5, -2, 1e2, +3)')->arguments()->value(),
        );

        $this->assertSame([1, 1.5, -2, 100.0, 3], $values);
    }

    #[DataProvider('stringProvider')]
    public function testStringsAreUnquotedAndUnescaped(string $expression, string $expected): void
    {
        $this->assertSame($expected, $this->parse($expression)->arguments()->value()[0]->value());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function stringProvider(): iterable
    {
        yield 'double quotes' => ['f("text")', 'text'];
        yield 'single quotes' => ["f('text')", 'text'];
        yield 'empty string' => ['f("")', ''];
        yield 'escaped double quote' => ['f("a\"b")', 'a"b'];
        yield 'escaped single quote' => ["f('a\\'b')", "a'b"];
        yield 'escaped backslash' => ['f("a\\\b")', 'a\b'];
        yield 'newline escape' => ['f("a\nb")', "a\nb"];
        yield 'other quote kind inside' => ['f("it\'s")', "it's"];
        yield 'spaces are kept' => ['f("  padded  ")', '  padded  '];
        yield 'punctuation is kept' => ['f("a.b(c)[d]")', 'a.b(c)[d]'];
    }

    /**
     * Quotes belonging to the content of a string must not be stripped together with the delimiters
     */
    #[DataProvider('quotedContentProvider')]
    public function testQuotesInsideStringsAreKept(string $expression, string $expected): void
    {
        $this->assertSame($expected, $this->parse($expression)->arguments()->value()[0]->value());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function quotedContentProvider(): iterable
    {
        yield 'single quotes inside double quotes' => ['f("\'quoted\'")', "'quoted'"];
        yield 'double quotes inside single quotes' => ["f('\"quoted\"')", '"quoted"'];
        yield 'only a single quote inside double quotes' => ['f("\'")', "'"];
        yield 'only a double quote inside single quotes' => ["f('\"')", '"'];
        yield 'quote at the end' => ['f("name\'")', "name'"];
        yield 'quote at the start' => ['f("\'name")', "'name"];
    }

    public function testNumbersSeparatedByCommasWithoutSpacesAreParsedAsSeparateArguments(): void
    {
        $values = array_map(
            static fn(AbstractNode $argument): mixed => $argument->value(),
            $this->parse('f(1,2,3)')->arguments()->value(),
        );

        $this->assertSame([1, 2, 3], $values);
    }

    public function testEmptyArray(): void
    {
        $node = $this->parse('f([])')->arguments()->value()[0];

        $this->assertInstanceOf(ArrayNode::class, $node);
        $this->assertSame([], $node->value());
        $this->assertSame([], $node->keys()->value());
    }

    public function testArrayWithImplicitKeys(): void
    {
        $node = $this->parse('f([1, 2])')->arguments()->value()[0];

        $this->assertCount(2, $node->value());
        $this->assertContainsOnlyInstancesOf(ImplicitArrayKeyNode::class, $node->keys()->value());
    }

    public function testArrayWithExplicitKeys(): void
    {
        $node = $this->parse('f(["a" => 1, b => 2, 3 => "c"])')->arguments()->value()[0];

        $keys = $node->keys()->value();

        $this->assertInstanceOf(StringNode::class, $keys[0]);
        $this->assertInstanceOf(IdentifierNode::class, $keys[1]);
        $this->assertInstanceOf(NumberNode::class, $keys[2]);
    }

    public function testArrayMixingImplicitAndExplicitKeys(): void
    {
        $node = $this->parse('f([1, "k" => 2, 3])')->arguments()->value()[0];

        $types = array_map(static fn(AbstractNode $key): string => $key->type(), $node->keys()->value());

        $this->assertSame(['implicit array key', 'string', 'implicit array key'], $types);
    }

    public function testNestedArrays(): void
    {
        $node = $this->parse('f([[1], ["a" => [2]]])')->arguments()->value()[0];

        $this->assertSame('array', $node->value()[0]->type());
        $this->assertSame('array', $node->value()[1]->type());
    }

    public function testWhitespaceIsInsignificant(): void
    {
        $this->assertEquals($this->parse('f(1, 2)'), $this->parse("  f  (  1 ,\n 2  )  "));
    }

    #[DataProvider('invalidExpressionProvider')]
    public function testInvalidExpressionsAreRejected(string $expression, string $message): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($message);
        $this->parse($expression);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidExpressionProvider(): iterable
    {
        yield 'empty expression' => ['', 'Unexpected token of type end, expected token of type identifier at position 0'];
        yield 'number at the top level' => ['1', 'Unexpected token "1" of type number, expected token of type identifier'];
        yield 'string at the top level' => ['"text"', 'expected token of type identifier'];
        yield 'array at the top level' => ['[1]', 'expected token of type identifier'];
        yield 'leading dot' => ['.a', 'Unexpected token "." of type punctuation, expected token of type identifier'];
        yield 'trailing dot' => ['a.', 'Unexpected token of type end, expected token of type identifier at position 2'];
        yield 'double dot' => ['a..b', 'Unexpected token "." of type punctuation, expected token of type identifier at position 2'];
        yield 'two identifiers' => ['a b', 'Unexpected token "b" of type identifier, expected end at position 2'];
        yield 'unclosed call' => ['a(', 'Unexpected token of type end'];
        yield 'unclosed call with arguments' => ['a(1', 'Unexpected token of type end'];
        yield 'unopened call' => ['a)', 'Unexpected token ")" of type punctuation, expected end at position 1'];
        yield 'trailing comma in arguments' => ['f(1,)', 'Unexpected token ")" of type punctuation at position 4'];
        yield 'leading comma in arguments' => ['f(,1)', 'Unexpected token "," of type punctuation at position 2'];
        yield 'missing comma in arguments' => ['f(1 a)', 'expected token "," of type punctuation'];
        yield 'unclosed brackets' => ['a[', 'Unexpected token of type end'];
        yield 'unclosed brackets with key' => ['a["x"', 'expected token "]" of type punctuation'];
        yield 'empty brackets' => ['a[]', 'Unexpected token "]" of type punctuation at position 2'];
        yield 'identifier in brackets' => ['a[b]', 'Unexpected token "b" of type identifier at position 2'];
        yield 'unclosed array' => ['f([1', 'Unexpected token of type end'];
        yield 'missing comma in array' => ['f([1 "a"])', 'expected token "," of type punctuation'];
        yield 'array as array key' => ['f([[1] => 2])', 'Unexpected token "=>" of type arrow at position 7'];
        yield 'arrow outside arrays' => ['f(1 => 2)', 'expected token "," of type punctuation'];
        yield 'arrow after an argument' => ['f(a => 2)', 'expected token "," of type punctuation'];
        yield 'punctuation as argument' => ['f(.)', 'Unexpected token "." of type punctuation at position 2'];
    }

    private function parse(string $expression): AbstractNode
    {
        return Parser::parseTokenStream(Tokenizer::tokenizeString($expression));
    }
}
