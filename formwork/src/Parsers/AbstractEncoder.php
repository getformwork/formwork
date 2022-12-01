<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;

abstract class AbstractEncoder extends AbstractParser
{
    /**
     * Encode an array of data to string
     */
    abstract public static function encode($data, array $options = []): string;

    /**
     * Encode an array of data to a given file
     */
    public static function encodeToFile($data, string $file, array $options = []): bool
    {
        return FileSystem::write($file, static::encode($data, $options));
    }
}
