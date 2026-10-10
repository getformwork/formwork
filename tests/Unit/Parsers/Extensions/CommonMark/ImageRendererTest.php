<?php

namespace Formwork\Tests\Unit\Parsers\Extensions\CommonMark;

use Formwork\Cms\Site;
use Formwork\Parsers\Extensions\CommonMark\ImageRenderer;
use Formwork\Parsers\Markdown;
use Formwork\Tests\TestCase;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\Config\Configuration;
use Nette\Schema\Expect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ImageRenderer::class)]
final class ImageRendererTest extends TestCase
{
    public function testImageIsRenderedAsASelfClosingElement(): void
    {
        $this->assertSame("<p><img src=\"/cat.png\" alt=\"A cat\"></p>\n", $this->parse('![A cat](cat.png)'));
    }

    public function testTitleIsRendered(): void
    {
        $this->assertStringContainsString('title="Tom"', $this->parse('![A cat](cat.png "Tom")'));
    }

    public function testAltIsOmittedWhenEmpty(): void
    {
        $html = $this->parse('![](cat.png)');

        $this->assertStringNotContainsString('alt=', $html);
        $this->assertStringContainsString('src="/cat.png"', $html);
    }

    public function testAltIsBuiltFromTheTextOfNestedInlines(): void
    {
        $html = $this->parse('![A *very* **big** `cat`](cat.png)');

        $this->assertStringContainsString('alt="A very big cat"', $html);
    }

    public function testAltKeepsLineBreaks(): void
    {
        $html = $this->parse("![first\nsecond](cat.png)");

        $this->assertStringContainsString("alt=\"first\nsecond\"", $html);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileValues(): iterable
    {
        yield 'alt' => ['![" onerror="alert(1)](cat.png)'];
        yield 'title' => ['![x](cat.png "\" onerror=\"alert(1)")'];
        yield 'destination' => ['![x](<cat.png" onerror="alert(1)>)'];
        yield 'angle brackets in alt' => ['![<script>alert(1)</script>](cat.png)'];
    }

    #[DataProvider('hostileValues')]
    public function testHostileValuesCannotBreakOutOfTheAttributes(string $markdown): void
    {
        $html = $this->parse($markdown);

        $document = new \DOMDocument();
        @$document->loadHTML('<body>' . $html);

        $this->assertSame(0, $document->getElementsByTagName('script')->length);
        $image = $document->getElementsByTagName('img')->item(0);
        $this->assertNotNull($image);
        $this->assertFalse($image->hasAttribute('onerror'));
    }

    public function testUnsafeDestinationsAreEmptiedWhenTheyAreForbidden(): void
    {
        $renderer = new ImageRenderer();
        $renderer->setConfiguration($this->configuration(allowUnsafeLinks: false));

        $image = new Image('javascript:alert(1)', 'label');
        $image->appendChild(new Text('label'));

        $this->assertStringContainsString('src=""', (string) $renderer->render($image, $this->childRenderer()));
    }

    public function testUnsafeDestinationsAreKeptWhenAllowed(): void
    {
        $renderer = new ImageRenderer();
        $renderer->setConfiguration($this->configuration(allowUnsafeLinks: true));

        $image = new Image('data:image/png;base64,AAAA', 'label');

        $this->assertStringContainsString('src="data:image/png;base64,AAAA"', (string) $renderer->render($image, $this->childRenderer()));
    }

    public function testJavascriptImagesNeverReachTheFinalHtml(): void
    {
        $this->assertStringNotContainsString('javascript:', $this->parse('![x](javascript:alert(1))'));
    }

    public function testExistingAttributesAreMergedIntoTheElement(): void
    {
        $renderer = new ImageRenderer();
        $renderer->setConfiguration($this->configuration(allowUnsafeLinks: false));
        $image = new Image('cat.png', 'label');
        $image->data->set('attributes', ['class' => 'wide', 'src' => 'ignored']);

        $html = (string) $renderer->render($image, $this->childRenderer());

        $this->assertStringContainsString('class="wide"', $html);
        $this->assertStringContainsString('src="cat.png"', $html);
    }

    public function testXmlRepresentation(): void
    {
        $renderer = new ImageRenderer();
        $image = new Image('cat.png', 'label', 'Tom');

        $this->assertSame('image', $renderer->getXmlTagName($image));
        $this->assertSame(['destination' => 'cat.png', 'title' => 'Tom'], $renderer->getXmlAttributes($image));
        $this->assertSame(['destination' => 'cat.png', 'title' => ''], $renderer->getXmlAttributes(new Image('cat.png', 'label')));
    }

    private function parse(string $markdown): string
    {
        $site = $this->createStub(Site::class);
        $site->method('uri')->willReturnCallback(static fn(string $uri): string => $uri);

        return Markdown::parse($markdown, ['site' => $site]);
    }

    private function configuration(bool $allowUnsafeLinks): Configuration
    {
        $configuration = new Configuration();
        $configuration->addSchema('allow_unsafe_links', Expect::bool(true));
        $configuration->merge(['allow_unsafe_links' => $allowUnsafeLinks]);
        return $configuration;
    }

    private function childRenderer(): ChildNodeRendererInterface
    {
        return $this->createStub(ChildNodeRendererInterface::class);
    }
}
