<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;

abstract class AbstractParser
{
    /**
     * Parse input from a string
     *
     * @param string $input
     * @param array  $options
     *
     * @return mixed
     */
    abstract public static function parse($input, array $options = []);

    /**
     * Parse file contents
     *
     * @param string $file
     * @param array  $options
     *
     * @return mixed
     */
    public static function parseFile($file, array $options = [])
    {
        return static::parse(FileSystem::read($file), $options);
    }
}
