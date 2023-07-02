<?php

namespace Formwork\Interpolator;

class Interpolator
{
    /**
     * Interpolate the given string
     */
    public static function interpolate(string $string, array $vars)
    {
        $interpolator = new NodeInterpolator(Parser::parseTokenStream(Tokenizer::tokenizeString($string)), $vars);
        return $interpolator->interpolate();
    }
}
