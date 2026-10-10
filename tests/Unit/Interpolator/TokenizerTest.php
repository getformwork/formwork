<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Errors\SyntaxError;
use Formwork\Interpolator\Token;
use Formwork\Interpolator\Tokenizer;
use Formwork\Interpolator\TokenizerInterface;
use Formwork\Interpolator\TokenStream;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Tokenizer::class)]
final class TokenizerTest extends TestCase
{
    public function testTokenizerImplementsItsInterface(): void
    {
        $this->assertInstanceOf(TokenizerInterface::class, new Tokenizer(''));
    }

    public function testEmptyInputProducesOnlyTheEndToken(): void
    {
        $this->assertSame([['end', null, 0]], $this->tokens(''));
    }

    public function testWhitespaceIsIgnored(): void
    {
        $this->assertSame([['end', null, 5]], $this->tokens(" \t\n \r"));
    }

    #[DataProvider('tokenProvider')]
    public function testTokenization(string $input, array $expected): void
    {
        $this->assertSame($expected, $this->tokens($input));
    }

    /**
     * @return iterable<string, array{string, list<array{string, ?string, int}>}>
     */
    public static function tokenProvider(): iterable
    {
        yield 'identifier' => ['name', [['identifier', 'name', 0], ['end', null, 4]]];
        yield 'identifier with digits and underscores' => ['_my_Var2', [['identifier', '_my_Var2', 0], ['end', null, 8]]];
        yield 'two identifiers' => ['a b', [['identifier', 'a', 0], ['identifier', 'b', 2], ['end', null, 3]]];
        yield 'dot notation' => ['a.b', [['identifier', 'a', 0], ['punctuation', '.', 1], ['identifier', 'b', 2], ['end', null, 3]]];
        yield 'call' => ['a(b)', [['identifier', 'a', 0], ['punctuation', '(', 1], ['identifier', 'b', 2], ['punctuation', ')', 3], ['end', null, 4]]];
        yield 'brackets' => ['a[0]', [['identifier', 'a', 0], ['punctuation', '[', 1], ['number', '0', 2], ['punctuation', ']', 3], ['end', null, 4]]];
        yield 'integer' => ['42', [['number', '42', 0], ['end', null, 2]]];
        yield 'float' => ['1.25', [['number', '1.25', 0], ['end', null, 4]]];
        yield 'negative number' => ['-7', [['number', '-7', 0], ['end', null, 2]]];
        yield 'positive number' => ['+7', [['number', '+7', 0], ['end', null, 2]]];
        yield 'exponent' => ['1e3', [['number', '1e3', 0], ['end', null, 3]]];
        yield 'uppercase exponent with sign' => ['2.5E-3', [['number', '2.5E-3', 0], ['end', null, 6]]];
        yield 'single quoted string' => ["'text'", [['string', "'text'", 0], ['end', null, 6]]];
        yield 'double quoted string' => ['"text"', [['string', '"text"', 0], ['end', null, 6]]];
        yield 'empty string' => ['""', [['string', '""', 0], ['end', null, 2]]];
        yield 'string with an escaped quote' => ['"a\"b"', [['string', '"a\"b"', 0], ['end', null, 6]]];
        yield 'string with the other quote inside' => ['"it\'s"', [['string', '"it\'s"', 0], ['end', null, 6]]];
        yield 'string with punctuation inside' => ['"a.b(c)[d]"', [['string', '"a.b(c)[d]"', 0], ['end', null, 11]]];
        yield 'arrow' => ['=>', [['arrow', '=>', 0], ['end', null, 2]]];
        yield 'array with keys' => ['[a => 1]', [
            ['punctuation', '[', 0], ['identifier', 'a', 1], ['arrow', '=>', 3], ['number', '1', 6], ['punctuation', ']', 7], ['end', null, 8],
        ]];
        yield 'arguments with spaces' => ['f(1, 2)', [
            ['identifier', 'f', 0], ['punctuation', '(', 1], ['number', '1', 2], ['punctuation', ',', 3], ['number', '2', 5], ['punctuation', ')', 6], ['end', null, 7],
        ]];
        yield 'positions skip whitespace' => ['  a  .  b', [['identifier', 'a', 2], ['punctuation', '.', 5], ['identifier', 'b', 8], ['end', null, 9]]];
    }

    #[DataProvider('invalidInputProvider')]
    public function testUnexpectedCharactersAreRejected(string $input, string $message): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($message);
        Tokenizer::tokenizeString($input);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'dollar sign' => ['$a', 'Unexpected character "$" at position 0'];
        yield 'operator' => ['a + b', 'Unexpected character "+" at position 2'];
        yield 'unterminated single quote' => ["'abc", 'Unexpected character "\'" at position 0'];
        yield 'unterminated double quote' => ['f("abc', 'Unexpected character """ at position 2'];
        yield 'braces' => ['{a}', 'Unexpected character "{" at position 0'];
        yield 'semicolon' => ['a;', 'Unexpected character ";" at position 1'];
        yield 'assignment' => ['a = 1', 'Unexpected character "=" at position 2'];
        yield 'backtick' => ['`a`', 'Unexpected character "`" at position 0'];
        yield 'non ASCII identifier' => ['è', 'Unexpected character'];
    }

    public function testStringTokensKeepTheirQuotesAndEscapes(): void
    {
        $tokens = $this->tokens('"a\\\b\"c"');

        $this->assertSame('"a\\\b\"c"', $tokens[0][1]);
    }

    public function testNumbersFollowedByCommasWithoutSpacesAreSeparateTokens(): void
    {
        $this->assertSame(
            [['number', '1', 0], ['punctuation', ',', 1], ['number', '2', 2], ['end', null, 3]],
            $this->tokens('1,2'),
        );
    }

    public function testNumbersFollowedByOtherCharactersAreNotMergedWithThem(): void
    {
        $this->assertSame(
            [['number', '1', 0], ['punctuation', ')', 1], ['end', null, 2]],
            $this->tokens('1)'),
        );
    }

    public function testNumbersSeparatedBySpacesAreSeparateTokens(): void
    {
        $this->assertSame(
            [['number', '1', 0], ['number', '2', 2], ['end', null, 3]],
            $this->tokens('1 2'),
        );
    }

    public function testNumbersAreNotMergedWithFollowingLetters(): void
    {
        $tokens = $this->tokens('1x5');

        $this->assertNotSame([['number', '1x5', 0], ['end', null, 3]], $tokens);
    }

    public function testTokenizeStringIsEquivalentToTokenize(): void
    {
        $this->assertEquals((new Tokenizer('a.b'))->tokenize(), Tokenizer::tokenizeString('a.b'));
    }

    public function testTokenStreamCanBeConsumedAfterTokenization(): void
    {
        $stream = Tokenizer::tokenizeString('a');

        $this->assertInstanceOf(TokenStream::class, $stream);
        $this->assertSame('a', $stream->consume()->value());
        $this->assertTrue($stream->current()->test(Token::TYPE_END));
    }

    /**
     * @return list<array{string, ?string, int}>
     */
    private function tokens(string $input): array
    {
        $stream = Tokenizer::tokenizeString($input);

        $tokens = [];
        do {
            $token = $stream->current();
            $tokens[] = [$token->type(), $token->value(), $token->position()];
            if ($token->test(Token::TYPE_END)) {
                break;
            }
            $stream->consume();
        } while (true);

        return $tokens;
    }
}
