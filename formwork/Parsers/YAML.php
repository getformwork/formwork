<?php

namespace Formwork\Parsers;

use Formwork\Core\Formwork;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class YAML extends AbstractParser
{
    /**
     * Whether to use PHP yaml extension to emit, parse, both or none of the operations
     *
     * @var string
     */
    protected static $PHPYAMLmode;

    /**
     * Parse a YAML string
     *
     * @return array
     */
    public static function parse(string $input, array $options = [])
    {
        if (function_exists('yaml_parse') && ($options['usePHPYAML'] ?? static::PHPYAMLmode('parse'))) {
            if (strpos($input, "---\n") !== 0) {
                $input = "---\n" . $input;
            }
            return (array) yaml_parse($input);
        }
        return (array) SymfonyYaml::parse($input);
    }

    /**
     * Encode data to YAML format
     *
     * @return string
     */
    public static function encode(array $data, array $options = [])
    {
        $data = (array) $data;
        if (empty($data)) {
            return '';
        }
        if (function_exists('yaml_emit') && ($options['usePHPYAML'] ?? static::PHPYAMLmode('emit'))) {
            return preg_replace('/^---[\n ]|\n\.{3}$/', '', yaml_emit($data));
        }
        return SymfonyYaml::dump($data);
    }

    /**
     * Check if PHPHYAMLmode option matches a pattern
     */
    protected static function PHPYAMLmode(string $pattern)
    {
        if (static::$PHPYAMLmode === null) {
            $option = Formwork::instance()->option('parsers.use_php_yaml');
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
        return static::$PHPYAMLmode === $pattern || static::$PHPYAMLmode === 'all';
    }
}
