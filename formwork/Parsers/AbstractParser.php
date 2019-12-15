<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;

abstract class AbstractParser
{
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
