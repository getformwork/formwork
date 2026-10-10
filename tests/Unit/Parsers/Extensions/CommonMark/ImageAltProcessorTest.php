<?php

namespace Formwork\Tests\Unit\Parsers\Extensions\CommonMark;

use Formwork\Cms\Site;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Pages\Page;
use Formwork\Parsers\Extensions\CommonMark\ImageAltProcessor;
use Formwork\Parsers\Markdown;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImageAltProcessor::class)]
final class ImageAltProcessorTest extends TestCase
{
    public function testAltTextIsTakenFromTheFileMetadataWhenTheMarkdownOneIsEmpty(): void
    {
        $html = Markdown::parse('![](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => 'Metadata alt'])]);

        $this->assertStringContainsString('alt="Metadata alt"', $html);
    }

    public function testAltTextWrittenInMarkdownTakesPrecedenceOverTheMetadata(): void
    {
        $html = Markdown::parse('![written alt](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => 'Metadata alt'])]);

        $this->assertStringContainsString('alt="written alt"', $html);
        $this->assertStringNotContainsString('Metadata alt', $html);
    }

    public function testMarkdownAltIsKeptWhenTheFileHasNoMetadata(): void
    {
        $html = Markdown::parse('![written alt](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => null])]);

        $this->assertStringContainsString('alt="written alt"', $html);
    }

    public function testMarkdownAltIsKeptWhenTheFileIsUnknown(): void
    {
        $html = Markdown::parse('![written alt](other.jpg)', ['site' => $this->site('/', ['photo.jpg' => 'Metadata alt'])]);

        $this->assertStringContainsString('alt="written alt"', $html);
    }

    public function testMarkdownAltIsKeptWhenThePageIsUnknown(): void
    {
        $html = Markdown::parse('![written alt](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => 'Metadata alt']), 'baseRoute' => '/elsewhere/']);

        $this->assertStringContainsString('alt="written alt"', $html);
    }

    public function testPageIsLookedUpThroughTheBaseRoute(): void
    {
        $html = Markdown::parse('![](photo.jpg)', ['site' => $this->site('/blog/', ['photo.jpg' => 'From blog']), 'baseRoute' => '/blog/']);

        $this->assertStringContainsString('alt="From blog"', $html);
    }

    public function testMetadataIsEscapedInTheOutput(): void
    {
        $html = Markdown::parse('![](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => '"><script>alert(1)</script>'])]);

        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $this->assertSame(0, $document->getElementsByTagName('script')->length);
        $this->assertSame('"><script>alert(1)</script>', $document->getElementsByTagName('img')->item(0)?->getAttribute('alt'));
    }

    public function testExternalImagesDoNotConsultTheFiles(): void
    {
        $html = Markdown::parse('![remote](https://example.com/photo.jpg)', ['site' => $this->site('/', ['https://example.com/photo.jpg' => 'Should not be used'])]);

        $this->assertStringContainsString('alt="remote"', $html);
    }

    public function testOnlyImagesAreProcessed(): void
    {
        $html = Markdown::parse('[link](photo.jpg)', ['site' => $this->site('/', ['photo.jpg' => 'Metadata alt'])]);

        $this->assertStringNotContainsString('Metadata alt', $html);
    }

    /**
     * @param array<string, string|null> $alts
     */
    private function site(string $route, array $alts): Site
    {
        $files = $this->createStub(FileCollection::class);
        $files->method('get')->willReturnCallback(function (string $name) use ($alts): ?File {
            if (!array_key_exists($name, $alts)) {
                return null;
            }
            $file = $this->createStub(File::class);
            $file->method('get')->willReturnCallback(static fn(string $key): ?string => $key === 'alt' ? $alts[$name] : null);
            return $file;
        });

        $page = $this->createStub(Page::class);
        $page->method('files')->willReturn($files);

        $site = $this->createStub(Site::class);
        $site->method('findPage')->willReturnCallback(static fn(string $requested): ?Page => $requested === $route ? $page : null);
        $site->method('uri')->willReturnCallback(static fn(string $uri): string => $uri);
        return $site;
    }
}
