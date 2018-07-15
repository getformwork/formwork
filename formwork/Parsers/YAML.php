<?php

namespace Formwork\Parsers;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Spyc;

class YAML
{
    public static $PHPYAMLmode;

    public static function parse($input)
    {
        if (function_exists('yaml_parse') && static::PHPYAMLmode('parse')) {
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
        if (function_exists('yaml_emit') && static::PHPYAMLmode('emit')) {
            if (empty($data)) {
                return '';
            }
            return preg_replace('/^---[\n ]|\n\.{3}$/', '', yaml_emit((array) $data));
        }
        return Spyc::YAMLDump($data, false, 0, true);
    }

    protected static function PHPYAMLmode($pattern)
    {
        if (is_null(static::$PHPYAMLmode)) {
            $option = Formwork::instance()->option('parsers.use_php_yaml');
            if ($option) {
                switch (strtolower($option)) {
                    case 'all':
                        static::$PHPYAMLmode = 'all';
                        break;
                    case 'emit':
                        static::$PHPYAMLmode = 'emit';
                        break;
                    case 'parse':
                        static::$PHPYAMLmode = 'parse';
                        break;
                    case 'none':
                    default:
                        static::$PHPYAMLmode = false;
                        break;
                }
            }
        }
        return static::$PHPYAMLmode === $pattern || static::$PHPYAMLmode == 'all';
    }
}
