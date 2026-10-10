<?php

namespace Formwork\Tests\Unit\Parsers\Extensions\CommonMark;

use Formwork\Cms\Site;
use Formwork\Parsers\Extensions\CommonMark\FormworkExtension;
use Formwork\Parsers\Extensions\CommonMark\LinkBaseProcessor;
use Formwork\Parsers\Markdown;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LinkBaseProcessor::class)]
#[CoversClass(FormworkExtension::class)]
final class LinkBaseProcessorTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function relativeLinks(): iterable
    {
        yield 'sibling page' => ['[a](page)', '/blog/', '/site/blog/page/'];
        yield 'absolute path' => ['[a](/about)', '/blog/', '/site/about/'];
        yield 'parent page' => ['[a](../about)', '/blog/post/', '/site/blog/about/'];
        yield 'current directory' => ['[a](./page)', '/blog/', '/site/blog/page/'];
        yield 'query string' => ['[a](page?x=1)', '/blog/', '/site/blog/page/?x=1'];
        yield 'fragment' => ['[a](page#top)', '/blog/', '/site/blog/page/#top'];
        yield 'file next to the page' => ['[a](report.pdf)', '/blog/', '/site/blog/report.pdf'];
        yield 'html file' => ['[a](page.html)', '/blog/', '/site/blog/page.html'];
    }

    #[DataProvider('relativeLinks')]
    public function testRelativeLinksAreResolvedAgainstTheBaseRoute(string $markdown, string $baseRoute, string $expectedUri): void
    {
        $html = Markdown::parse($markdown, ['site' => $this->site(), 'baseRoute' => $baseRoute]);

        $this->assertSame('<p><a href="' . $expectedUri . '">a</a></p>' . "\n", $html);
    }

    public function testRelativeImagesAreResolvedToo(): void
    {
        $html = Markdown::parse('![alt](pic.png)', ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertStringContainsString('src="/site/blog/pic.png"', $html);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function untouchedLinks(): iterable
    {
        yield 'anchor' => ['[a](#section)', '#section'];
        yield 'https' => ['[a](https://example.com/x)', 'https://example.com/x'];
        yield 'http' => ['[a](http://example.com/x)', 'http://example.com/x'];
        yield 'mailto' => ['[a](mailto:me@example.com)', 'mailto:me@example.com'];
    }

    #[DataProvider('untouchedLinks')]
    public function testExternalAndSpecialLinksAreNotRewritten(string $markdown, string $expectedUri): void
    {
        $html = Markdown::parse($markdown, ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertSame('<p><a href="' . $expectedUri . '">a</a></p>' . "\n", $html);
    }

    public function testProtocolRelativeLinksAreNeverTurnedIntoLocalPaths(): void
    {
        $html = Markdown::parse('[a](//cdn.example.com/x.js)', ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertStringNotContainsString('/site//cdn.example.com', $html);
        $this->assertStringNotContainsString('/site/blog/', $html);
    }

    public function testEmptyLinkDestinationsAreHandled(): void
    {
        $html = Markdown::parse('[empty]()', ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertStringContainsString('empty', $html);
    }

    public function testEmptyImageDestinationsAreHandled(): void
    {
        $html = Markdown::parse('![empty]()', ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertStringContainsString('<img', $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dangerousDestinations(): iterable
    {
        yield 'javascript link' => ['[x](javascript:alert(1))'];
        yield 'javascript link uppercase' => ['[x](JaVaScRiPt:alert(1))'];
        yield 'javascript image' => ['![x](javascript:alert(1))'];
        yield 'data html link' => ['[x](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)'];
        yield 'vbscript link' => ['[x](vbscript:msgbox(1))'];
        yield 'encoded javascript link' => ['[x](&#106;avascript:alert(1))'];
    }

    #[DataProvider('dangerousDestinations')]
    public function testDangerousDestinationsNeverReachTheOutput(string $markdown): void
    {
        $html = Markdown::parse($markdown, ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertDoesNotMatchRegularExpression('/(java\s*script|vbscript|data:text\/html)\s*:/i', $html);
    }

    public function testRelativeLinksWithoutSiteDoNotCrash(): void
    {
        $html = Markdown::parse('[a](page)');

        $this->assertStringContainsString('page', $html);
    }

    public function testImagesWithoutSiteDoNotCrash(): void
    {
        $html = Markdown::parse('![alt](picture.png)');

        $this->assertStringContainsString('picture.png', $html);
    }

    public function testRelativeLinkInsideNestedMarkupIsStillRewritten(): void
    {
        $html = Markdown::parse("- **[a](page)**\n- > [b](other)", ['site' => $this->site(), 'baseRoute' => '/blog/']);

        $this->assertStringContainsString('href="/site/blog/page/"', $html);
        $this->assertStringContainsString('href="/site/blog/other/"', $html);
    }

    public function testDefaultBaseRouteIsTheRoot(): void
    {
        $html = Markdown::parse('[a](page)', ['site' => $this->site()]);

        $this->assertStringContainsString('href="/site/page/"', $html);
    }

    private function site(): Site
    {
        $site = $this->createStub(Site::class);
        $site->method('uri')->willReturnCallback(static fn(string $uri): string => '/site' . $uri);
        return $site;
    }
}
