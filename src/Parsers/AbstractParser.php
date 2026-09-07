<?php

namespace Formwork\Parsers;

use Formwork\Traits\StaticClass;
use Formwork\Utils\FileSystem;

abstract class AbstractParser
{
    use StaticClass;

    /**
     * Parse input from a string
     *
     * @param array<string, mixed> $options
     */
    abstract public static function parse(string $input, array $options = []): mixed;

    /**
     * Parse file contents
     *
     * @param array<string, mixed> $options
     */
    public static function parseFile(string $file, array $options = []): mixed
    {
        return static::parse(FileSystem::read($file), $options);
    }
}
