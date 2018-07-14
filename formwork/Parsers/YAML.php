<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;
use Spyc;

class YAML
{
    public static function parse($input)
    {
        return Spyc::YAMLLoadString($input);
    }

    public static function parseFile($file)
    {
        return static::parse(FileSystem::read($file));
    }

    public static function encode($data)
    {
        return Spyc::YAMLDump($data, false, 0, true);
    }
}
