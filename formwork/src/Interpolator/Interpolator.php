<?php

namespace Formwork\Interpolator;

class Interpolator
{
    /**
     * Interpolate the given string
     *
     * @param array<string, mixed> $vars
     */
    public static function interpolate(string $string, array $vars): mixed
    {
        $interpolator = new NodeInterpolator(Parser::parseTokenStream(Tokenizer::tokenizeString($string)), $vars);
        return $interpolator->interpolate();
    }
}
