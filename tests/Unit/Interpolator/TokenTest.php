<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Token;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Token::class)]
final class TokenTest extends TestCase
{
    public function testTokenExposesTypeValueAndPosition(): void
    {
        $token = new Token(Token::TYPE_IDENTIFIER, 'name', 7);

        $this->assertSame('identifier', $token->type());
        $this->assertSame('name', $token->value());
        $this->assertSame(7, $token->position());
    }

    public function testTokenTypesAreDistinct(): void
    {
        $types = [
            Token::TYPE_IDENTIFIER,
            Token::TYPE_NUMBER,
            Token::TYPE_STRING,
            Token::TYPE_PUNCTUATION,
            Token::TYPE_ARROW,
            Token::TYPE_END,
        ];

        $this->assertSame($types, array_values(array_unique($types)));
    }

    public function testTokensCanHaveNoValue(): void
    {
        $this->assertNull((new Token(Token::TYPE_END, null, 0))->value());
    }

    #[DataProvider('stringRepresentationProvider')]
    public function testStringRepresentation(Token $token, string $expected): void
    {
        $this->assertSame($expected, (string) $token);
    }

    /**
     * @return iterable<string, array{Token, string}>
     */
    public static function stringRepresentationProvider(): iterable
    {
        yield 'identifier' => [new Token(Token::TYPE_IDENTIFIER, 'name', 0), 'token "name" of type identifier'];
        yield 'punctuation' => [new Token(Token::TYPE_PUNCTUATION, '(', 0), 'token "(" of type punctuation'];
        yield 'end without a value' => [new Token(Token::TYPE_END, null, 0), 'token of type end'];
        yield 'empty value' => [new Token(Token::TYPE_STRING, '', 0), 'token "" of type string'];
    }

    public function testTestWithOnlyTheTypeIgnoresTheValue(): void
    {
        $token = new Token(Token::TYPE_PUNCTUATION, '(', 0);

        $this->assertTrue($token->test(Token::TYPE_PUNCTUATION));
        $this->assertFalse($token->test(Token::TYPE_NUMBER));
    }

    public function testTestWithTypeAndValueMatchesBoth(): void
    {
        $token = new Token(Token::TYPE_PUNCTUATION, '(', 0);

        $this->assertTrue($token->test(Token::TYPE_PUNCTUATION, '('));
        $this->assertFalse($token->test(Token::TYPE_PUNCTUATION, ')'));
        $this->assertFalse($token->test(Token::TYPE_NUMBER, '('));
    }

    public function testTestWithAnExplicitNullValueMatchesOnlyTokensWithoutValue(): void
    {
        $end = new Token(Token::TYPE_END, null, 0);
        $identifier = new Token(Token::TYPE_IDENTIFIER, 'name', 0);

        $this->assertTrue($end->test(Token::TYPE_END, null));
        $this->assertFalse($identifier->test(Token::TYPE_IDENTIFIER, null));
    }

    public function testTestComparesValuesStrictly(): void
    {
        $token = new Token(Token::TYPE_NUMBER, '0', 0);

        $this->assertTrue($token->test(Token::TYPE_NUMBER, '0'));
        $this->assertFalse($token->test(Token::TYPE_NUMBER, ''));
    }
}
