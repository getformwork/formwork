<?php

namespace Formwork\Interpolator;

use Formwork\Interpolator\Errors\SyntaxError;
use Formwork\Interpolator\Nodes\AbstractNode;
use Formwork\Interpolator\Nodes\ArgumentsNode;
use Formwork\Interpolator\Nodes\ArrayKeysNode;
use Formwork\Interpolator\Nodes\ArrayNode;
use Formwork\Interpolator\Nodes\IdentifierNode;
use Formwork\Interpolator\Nodes\ImplicitArrayKeyNode;
use Formwork\Interpolator\Nodes\NumberNode;
use Formwork\Interpolator\Nodes\StringNode;

class Parser implements ParserInterface
{
    public function __construct(
        protected TokenStream $tokenStream,
    ) {}

    /**
     * Parse the tokens
     */
    public function parse(): AbstractNode
    {
        $identifierNode = $this->parseIdentifierToken();
        $this->tokenStream->expectEnd();
        return $identifierNode;
    }

    /**
     * Parse a given TokenStream object
     */
    public static function parseTokenStream(TokenStream $tokenStream): AbstractNode
    {
        $static = new static($tokenStream);
        return $static->parse();
    }

    /**
     * Parse an identifier token
     */
    protected function parseIdentifierToken(): IdentifierNode
    {
        $token = $this->tokenStream->expect(Token::TYPE_IDENTIFIER);

        $traverse = null;

        $arguments = null;

        if ($this->tokenStream->current()->test(Token::TYPE_PUNCTUATION, '(')) {
            $arguments = $this->parseArguments();
        }

        if ($this->tokenStream->current()->test(Token::TYPE_PUNCTUATION, '.')) {
            $traverse = $this->parseDotNotation();
        }

        if ($this->tokenStream->current()->test(Token::TYPE_PUNCTUATION, '[')) {
            $traverse = $this->parseBracketsNotation();
        }

        // @phpstan-ignore-next-line
        return new IdentifierNode($token->value(), $arguments, $traverse);
    }

    /**
     * Parse a number token
     */
    protected function parseNumberToken(): NumberNode
    {
        $token = $this->tokenStream->expect(Token::TYPE_NUMBER);
        // @phpstan-ignore-next-line
        return new NumberNode($token->value() + 0);
    }

    /**
     * Parse a string token
     */
    protected function parseStringToken(): StringNode
    {
        $token = $this->tokenStream->expect(Token::TYPE_STRING);
        return new StringNode(stripcslashes(trim((string) $token->value(), '\'"')));
    }

    /**
     * Parse dot notation
     */
    protected function parseDotNotation(): IdentifierNode
    {
        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, '.');
        return $this->parseIdentifierToken();
    }

    /**
     * Parse brackets notation
     */
    protected function parseBracketsNotation(): AbstractNode
    {
        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, '[');

        $token = $this->tokenStream->current();

        $key = match ($token->type()) {
            Token::TYPE_NUMBER => $this->parseNumberToken(),
            Token::TYPE_STRING => $this->parseStringToken(),
            default            => throw new SyntaxError(sprintf('Unexpected %s at position %d', $token, $token->position())),
        };

        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, ']');

        return $key;
    }

    /**
     * Parse arguments
     */
    protected function parseArguments(): ArgumentsNode
    {
        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, '(');

        $arguments = [];

        while (!$this->tokenStream->current()->test(Token::TYPE_PUNCTUATION, ')')) {
            if ($arguments !== []) {
                $this->tokenStream->expect(Token::TYPE_PUNCTUATION, ',');
            }
            $arguments[] = $this->parseExpression();
        }

        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, ')');

        return new ArgumentsNode($arguments);
    }

    /**
     * Parse expression
     */
    protected function parseExpression(): AbstractNode
    {
        $token = $this->tokenStream->current();

        switch ($token->type()) {
            case Token::TYPE_IDENTIFIER:
                return $this->parseIdentifierToken();

            case Token::TYPE_NUMBER:
                return $this->parseNumberToken();

            case Token::TYPE_STRING:
                return $this->parseStringToken();

            case Token::TYPE_PUNCTUATION:
                if ($token->value() === '[') {
                    return $this->parseArrayExpression();
                }
                // no break for other punctuation characters

            default:
                throw new SyntaxError(sprintf('Unexpected %s at position %d', $token, $token->position()));
        }
    }

    /**
     * Parse array expression
     */
    protected function parseArrayExpression(): ArrayNode
    {
        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, '[');

        $elements = [];

        $keys = [];

        while (!$this->tokenStream->current()->test(Token::TYPE_PUNCTUATION, ']')) {
            if ($elements !== []) {
                $this->tokenStream->expect(Token::TYPE_PUNCTUATION, ',');
            }

            $value = $this->parseExpression();

            if ($this->tokenStream->current()->test(Token::TYPE_ARROW)) {
                $arrow = $this->tokenStream->consume();

                if ($value->type() === ArrayNode::TYPE) {
                    throw new SyntaxError(sprintf('Unexpected %s at position %d', $arrow, $arrow->position()));
                }

                $key = $value;
                $value = $this->parseExpression();
            } else {
                $key = new ImplicitArrayKeyNode();
            }

            $elements[] = $value;
            $keys[] = $key;
        }

        $this->tokenStream->expect(Token::TYPE_PUNCTUATION, ']');

        return new ArrayNode($elements, new ArrayKeysNode($keys));
    }
}
