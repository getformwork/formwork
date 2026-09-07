<?php

namespace Formwork\Interpolator;

interface TokenizerInterface
{
    public function __construct(string $input);

    /**
     * Tokenize input
     */
    public function tokenize(): TokenStream;

    /**
     * Tokenize a string
     */
    public static function tokenizeString(string $string): TokenStream;
}
