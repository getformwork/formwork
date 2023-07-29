<?php

namespace Formwork\Parsers;

use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml extends AbstractEncoder
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
        return SymfonyYaml::dump($data, inline: 4);
    }
}
