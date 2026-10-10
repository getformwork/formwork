<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Errors\SyntaxError;
use Formwork\Interpolator\Token;
use Formwork\Interpolator\TokenStream;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TokenStream::class)]
final class TokenStreamTest extends TestCase
{
    public function testCurrentReturnsTheFirstToken(): void
    {
        $stream = $this->stream();

        $this->assertSame('a', $stream->current()->value());
    }

    public function testCurrentDoesNotAdvanceTheStream(): void
    {
        $stream = $this->stream();

        $this->assertSame($stream->current(), $stream->current());
    }

    public function testConsumeReturnsTheCurrentTokenAndAdvances(): void
    {
        $stream = $this->stream();

        $this->assertSame('a', $stream->consume()->value());
        $this->assertSame('(', $stream->current()->value());
        $this->assertSame('(', $stream->consume()->value());
        $this->assertSame(')', $stream->current()->value());
    }

    public function testEndTokenCannotBeConsumed(): void
    {
        $stream = $this->stream();
        $stream->consume();
        $stream->consume();
        $stream->consume();

        $this->assertTrue($stream->current()->test(Token::TYPE_END));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unexpected end at position 4');
        $stream->consume();
    }

    public function testExpectConsumesAMatchingTokenByType(): void
    {
        $stream = $this->stream();

        $token = $stream->expect(Token::TYPE_IDENTIFIER);

        $this->assertSame('a', $token->value());
        $this->assertSame('(', $stream->current()->value());
    }

    public function testExpectConsumesAMatchingTokenByTypeAndValue(): void
    {
        $stream = $this->stream();
        $stream->consume();

        $this->assertSame('(', $stream->expect(Token::TYPE_PUNCTUATION, '(')->value());
    }

    public function testExpectRejectsATokenOfAnotherType(): void
    {
        $stream = $this->stream();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unexpected token "a" of type identifier, expected token of type number at position 0');
        $stream->expect(Token::TYPE_NUMBER);
    }

    public function testExpectRejectsATokenWithAnotherValue(): void
    {
        $stream = $this->stream();
        $stream->consume();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unexpected token "(" of type punctuation, expected token ")" of type punctuation at position 1');
        $stream->expect(Token::TYPE_PUNCTUATION, ')');
    }

    public function testFailedExpectationDoesNotAdvanceTheStream(): void
    {
        $stream = $this->stream();

        try {
            $stream->expect(Token::TYPE_NUMBER);
        } catch (SyntaxError) {
        }

        $this->assertSame('a', $stream->current()->value());
    }

    public function testExpectEndSucceedsOnlyAtTheEnd(): void
    {
        $stream = $this->stream();
        $stream->consume();
        $stream->consume();
        $stream->consume();

        $stream->expectEnd();

        $this->assertTrue($stream->current()->test(Token::TYPE_END));
    }

    public function testExpectEndFailsBeforeTheEnd(): void
    {
        $stream = $this->stream();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unexpected token "a" of type identifier, expected end at position 0');
        $stream->expectEnd();
    }

    public function testSyntaxErrorsAreErrorsAndNotExceptions(): void
    {
        $stream = $this->stream();

        try {
            $stream->expectEnd();
        } catch (\Exception) {
            $this->fail('Syntax errors are not exceptions.');
        } catch (SyntaxError $error) {
            $this->assertInstanceOf(\Error::class, $error);
        }
    }

    private function stream(): TokenStream
    {
        return new TokenStream([
            new Token(Token::TYPE_IDENTIFIER, 'a', 0),
            new Token(Token::TYPE_PUNCTUATION, '(', 1),
            new Token(Token::TYPE_PUNCTUATION, ')', 2),
            new Token(Token::TYPE_END, null, 3),
        ]);
    }
}
