<?php

namespace Formwork\Parsers;

use Formwork\Formwork;
use Formwork\Utils\Str;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class YAML extends AbstractEncoder
{
    /**
     * Document start delimiter required by yaml_parse()
     */
    protected const DOCUMENT_START = "---\n";

    /**
     * Regex matching document delimiters to be removed from yaml_emit() output
     */
    protected const DOCUMENT_DELIMITERS_REGEX = '/^-{3}[\n ]|\n\.{3}$/';

    /**
     * Parse a YAML string
     */
    public static function parse(string $input, array $options = []): array
    {
        if (function_exists('yaml_parse') && ($options['usePHPYAML'] ?? static::usePHPYAMLparse())) {
            if (!Str::startsWith($input, self::DOCUMENT_START)) {
                $input = self::DOCUMENT_START . $input;
            }
            return (array) yaml_parse($input);
        }
        return (array) SymfonyYaml::parse($input);
    }

    /**
     * Encode data to YAML format
     */
    public static function encode($data, array $options = []): string
    {
        if (empty($data)) {
            return '';
        }
        if (function_exists('yaml_emit') && ($options['usePHPYAML'] ?? static::usePHPYAMLemit())) {
            return preg_replace(self::DOCUMENT_DELIMITERS_REGEX, '', yaml_emit($data));
        }
        return SymfonyYaml::dump($data);
    }

    /**
     * Return whether yaml_parse() can be used
     */
    protected static function usePHPYAMLparse(): bool
    {
        $option = Formwork::instance()->config()->get('parsers.usePhpYaml');
        return $option === 'parse' || $option === 'all';
    }

    /**
     * Return whether yaml_emit() can be used
     */
    protected static function usePHPYAMLemit(): bool
    {
        $option = Formwork::instance()->config()->get('parsers.usePhpYaml');
        return $option === 'emit' || $option === 'all';
    }
}
