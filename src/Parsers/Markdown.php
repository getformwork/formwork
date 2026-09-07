<?php

namespace Formwork\Parsers;

use Formwork\Parsers\Extensions\CommonMark\FormworkExtension;
use Formwork\Parsers\Extensions\CommonMark\ImageRenderer;
use Formwork\Sanitizer\HtmlSanitizer;
use Formwork\Utils\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\StringContainerHelper;
use UnexpectedValueException;

final class Markdown extends AbstractParser
{
    /**
     * Parse a Markdown string
     *
     * @param array<string, mixed> $options
     */
    public static function parse(string $input, array $options = []): string
    {

        ['config' => $config, 'extensions' => $extensions] = self::parseOptions($options);

        $environment = new Environment($config);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FormworkExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new TableExtension());
        $environment->addRenderer(Image::class, new ImageRenderer());

        $addedExtensions = [];

        foreach ($environment->getExtensions() as $extension) {
            $addedExtensions[] = $extension::class;
        }

        foreach ($extensions as $extension) {
            if (in_array($extension, $addedExtensions, true)) {
                continue;
            }
            $addedExtensions[] = $extension;
            $environment->addExtension(new $extension());
        }

        $markdownConverter = new MarkdownConverter($environment);

        $renderedContent = $markdownConverter->convert($input);

        $htmlSanitizer = new HtmlSanitizer();

        return $htmlSanitizer->sanitize($renderedContent);
    }

    /**
     * Parse options for Markdown parsing
     *
     * @param array<string, mixed> $options
     *
     * @return array{config: array<string, mixed>, extensions: list<class-string<ExtensionInterface>>}
     */
    private static function parseOptions(array $options): array
    {
        $defaults = [
            'allowHtml'            => false,
            'addHeadingIds'        => false,
            'commonmarkExtensions' => [],
        ];

        $formworkExtensionKeys = [
            'site',
            'baseRoute',
        ];

        $options = [...$defaults, ...$options];

        $defaultAttributes = [];

        if ($options['addHeadingIds']) {
            $defaultAttributes[Heading::class] = [
                'id' => fn(Heading $heading) => Str::slug(StringContainerHelper::getChildText($heading)),
            ];
        }

        $config = [
            'html_input'         => $options['allowHtml'] ? 'allow' : 'escape',
            'max_nesting_level'  => 10,
            'default_attributes' => $defaultAttributes,
            'formwork'           => array_intersect_key($options, array_flip($formworkExtensionKeys)),
        ];

        $extensions = [];

        foreach ($options['commonmarkExtensions'] as $class => $data) {
            $enabled = $data['enabled'] ?? true;
            $extensionConfig = $data['config'] ?? [];

            if (!class_exists($class) || !is_subclass_of($class, ExtensionInterface::class)) {
                throw new UnexpectedValueException(
                    sprintf('Invalid CommonMark extension "%s". The class must exist and implement "%s"', $class, ExtensionInterface::class)
                );
            }

            if ($enabled) {
                $extensions[] = $class;
                $config = [...$config, ...$extensionConfig];
            }
        }

        return ['config' => $config, 'extensions' => $extensions];
    }
}
