<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;

abstract class AbstractEncoder extends AbstractParser
{
    /**
     * Encode an array of data to string
     *
     * @param array<string, mixed> $options
     */
    abstract public static function encode(mixed $data, array $options = []): string;

    /**
     * Encode an array of data to a given file
     *
     * @param array<string, mixed> $options
     */
    public static function encodeToFile(mixed $data, string $file, array $options = []): bool
    {
        return FileSystem::write($file, static::encode($data, $options));
    }
}
