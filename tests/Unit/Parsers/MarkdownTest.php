<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Cms\Site;
use Formwork\Parsers\Markdown;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Parsers\Fixtures\CommonMarkExtensionFixture;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

        $this->assertSame("<p>An <em class=\"custom\">emphasized</em> word</p>\n", Markdown::parse('An *emphasized* word', $options));
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
        $markdown = "# Title\n\nA *simple* [link](https://example.com).";

        $this->assertSame(Markdown::parse($markdown), Markdown::parse($markdown, $options));
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

    public function testExtensionsAreEnabledByDefault(): void
    {
        $options = ['commonmarkExtensions' => [CommonMarkExtensionFixture::class => []]];

        $this->assertSame("<p><em class=\"custom\">word</em></p>\n", Markdown::parse('*word*', $options));
    }

    public function testDisabledCommonMarkExtensionsAreNotRegistered(): void
    {
        $options = [
            'commonmarkExtensions' => [
                CommonMarkExtensionFixture::class => [
                    'enabled' => false,
                ],
            ],
        ];

        $this->assertSame("<p><em>word</em></p>\n", Markdown::parse('*word*', $options));
    }

    public function testRawHtmlIsEscapedByDefault(): void
    {
        $html = Markdown::parse('Text <b>bold</b> and <script>alert(1)</script>');

        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function testAllowedRawHtmlIsStillSanitized(): void
    {
        $html = Markdown::parse('Text <b>bold</b> <script>alert(1)</script> <img src="x.png" onerror="alert(2)">', ['allowHtml' => true]);

        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    #[DataProvider('unsafeLinkProvider')]
    public function testUnsafeLinkSchemesAreRemoved(string $markdown, array $options): void
    {
        $html = Markdown::parse($markdown, $options);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('href', $html);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function unsafeLinkProvider(): iterable
    {
        yield 'Markdown link' => ['[click](javascript:alert(1))', []];
        yield 'Markdown link with mixed case' => ['[click](JaVaScRiPt:alert(1))', []];
        yield 'HTML link' => ['<a href="javascript:alert(1)">click</a>', ['allowHtml' => true]];
    }

    public function testTablesAreSupported(): void
    {
        $html = Markdown::parse("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>a</th>', $html);
        $this->assertStringContainsString('<td>2</td>', $html);
    }

    public function testHeadingsHaveNoIdByDefault(): void
    {
        $this->assertSame("<h2>Section</h2>\n", Markdown::parse('## Section'));
    }

    public function testHeadingsWithoutSlugCharactersDoNotGetAnEmptyId(): void
    {
        $this->assertStringNotContainsString('id=""', Markdown::parse('## !!!', ['addHeadingIds' => true]));
    }

    public function testHeadingIdsAreUniqueWhenTitlesRepeat(): void
    {
        $html = Markdown::parse("## Same\n\n## Same", ['addHeadingIds' => true]);

        preg_match_all('/id="([^"]*)"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertCount(2, array_unique($matches[1]));
    }
}
