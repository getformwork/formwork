<?php

namespace Formwork\Parsers;

use Formwork\App;
use Formwork\Parsers\Extensions\CommonMark\LinkBaseExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class Markdown extends AbstractParser
{
    /**
     * Parse a Markdown string
     *
     * @param array<string, mixed> $options
     */
    public static function parse(string $input, array $options = []): string
    {
        $safeMode = App::instance()->config()->get('system.content.safeMode', true);

        $environment = new Environment([
            'html_input'         => $safeMode ? 'escape' : 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 10,
            'formwork'           => $options,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new LinkBaseExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());

        $markdownConverter = new MarkdownConverter($environment);

        return $markdownConverter->convert($input);
    }
}
