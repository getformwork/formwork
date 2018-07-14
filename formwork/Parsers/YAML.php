<?php

namespace Formwork\Parsers;

use Formwork\Utils\FileSystem;
use Spyc;

class YAML
{
    public static function parse($input)
    {
        if (function_exists('yaml_parse')) {
            if (!preg_match('/^---\n/', $input)) {
                $input = "---\n" . $input;
            }
            return (array) yaml_parse($input);
        }
        return Spyc::YAMLLoadString($input);
    }

    public static function parseFile($file)
    {
        return static::parse(FileSystem::read($file));
    }

    public static function encode($data)
    {
        if (function_exists('yaml_emit')) {
            if (empty($data)) {
                return '';
            }
            return preg_replace('/^---[\n ]|\n\.{3}$/', '', yaml_emit((array) $data));
        }
        return Spyc::YAMLDump($data, false, 0, true);
    }
}
