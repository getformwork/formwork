<?php

namespace Formwork\Parsers;

use Formwork\Parsers\Extensions\ParsedownExtra;

class Markdown extends AbstractParser
{
    /**
     * Parse a Markdown string
     *
     * @param string $input
     * @param array  $options
     *
     * @return string
     */
    public static function parse($input, array $options = array())
    {
        return @ParsedownExtra::instance()->text($input, $options);
    }
}
