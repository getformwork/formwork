<?php

namespace Formwork\Tests\Unit\Parsers\Fixtures;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Extension marking the emphasized text with a custom class
 */
class CommonMarkExtensionFixture implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(Emphasis::class, new class implements NodeRendererInterface {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                return '<em class="custom">' . $childRenderer->renderNodes($node->children()) . '</em>';
            }
        }, 10);
    }
}
