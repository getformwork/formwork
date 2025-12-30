<?php

namespace Formwork\Tests\Parsers;

use Formwork\Cms\Site;
use Formwork\Parsers\Markdown;
use Formwork\Tests\Parsers\Fixtures\CommonMarkExtensionFixture;
use Formwork\Tests\TestCase;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use UnexpectedValueException;

#[CoversClass(Markdown::class)]
final class MarkdownTest extends TestCase
{
    public function testParse(): void
    {
        $markdown = "# Hello, World!\n\nThis is a **bold** statement.";
        $expectedHtml = "<h1>Hello, World!</h1>\n<p>This is a <strong>bold</strong> statement.</p>\n";

        $this->assertSame($expectedHtml, Markdown::parse($markdown));
    }

    public function testParseWithSiteUri(): void
    {
        $site = $this->createStub(Site::class);

        $site->method('uri')
            ->willReturnArgument(0);

        $markdown = "![Alt text](image.jpg)\n\n[Link text](https://example.com)";
        $expectedHtml = "<p><img src=\"/image.jpg\" alt=\"Alt text\"></p>\n<p><a href=\"https://example.com\">Link text</a></p>\n";

        $this->assertSame($expectedHtml, Markdown::parse($markdown, ['site' => $site]));
    }

    public function testParseReturnsHeadingsIdsWhenOptionEnabled(): void
    {
        $markdown = "## Section One\n\n## Section Two";
        $expectedHtml = "<h2 id=\"section-one\">Section One</h2>\n<h2 id=\"section-two\">Section Two</h2>\n";

        $this->assertSame($expectedHtml, Markdown::parse($markdown, ['addHeadingIds' => true]));
    }

    public function testParseWithCommonMarkExtensions(): void
    {
        $options = [
            'commonmarkExtensions' => [
                CommonMarkExtensionFixture::class => [
                    'enabled' => true,
                ],
            ],
        ];

        $this->expectNotToPerformAssertions();
        Markdown::parse('', $options);
    }

    public function testParseWithCommonMarkExtensionsDoesNotAddEnvironmentExtensions(): void
    {
        $options = [
            'commonmarkExtensions' => [
                CommonMarkCoreExtension::class => [
                    'enabled' => true,
                ],
            ],
        ];

        $this->expectNotToPerformAssertions();
        Markdown::parse('', $options);
    }

    public function testParseThrowsUnexpectedValueExceptionOnInvalidCommonMarkExtension(): void
    {
        $options = [
            'commonmarkExtensions' => [
                stdClass::class => [
                    'enabled' => true,
                ],
            ],
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid CommonMark extension "stdClass"');
        Markdown::parse('', $options);
    }
}
