<?php

namespace Formwork\Interpolator;

use Formwork\Interpolator\Nodes\AbstractNode;

interface ParserInterface
{
    public function __construct(TokenStream $tokenStream);

    /**
     * Parse the tokens
     */
    public function parse(): AbstractNode;

    /**
     * Parse a given TokenStream object
     */
    public static function parseTokenStream(TokenStream $tokenStream): AbstractNode;
}
