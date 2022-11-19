<?php

namespace Formwork\Parsers;

use Formwork\Traits\StaticClass;
use Formwork\Utils\FileSystem;

abstract class AbstractParser
{
    use StaticClass;

    /**
     * Parse input from a string
     */
    abstract public static function parse(string $input, array $options = []);

    /**
     * Parse file contents
     */
    public static function parseFile(string $file, array $options = [])
    {
        return static::parse(FileSystem::read($file), $options);
    }
}
