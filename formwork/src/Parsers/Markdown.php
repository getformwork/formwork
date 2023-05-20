<?php

namespace Formwork\Parsers;

use Formwork\Parsers\Extensions\CommonMark\LinkBaseExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class Markdown extends AbstractParser
{
    /**
     * Parse a Markdown string
     */
    public static function parse(string $input, array $options = []): string
    {
        $environment = new Environment(['formwork' => $options]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new LinkBaseExtension());

        $converter = new MarkdownConverter($environment);

        return $converter->convert($input);
    }
}
