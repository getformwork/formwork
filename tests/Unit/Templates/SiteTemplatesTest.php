<?php

namespace Formwork\Tests\Unit\Templates;

use DOMDocument;
use DOMXPath;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\PageCollectionFactory;
use Formwork\Pages\PageFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use Formwork\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders the default site layout with hostile content to ensure dynamic values are escaped
 */
#[CoversNothing]
#[UsesApplication]
#[UsesTempDirectory]
final class SiteTemplatesTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();
    }

    #[DataProvider('hostileTitleProvider')]
    public function testPageTitleIsEscapedInTheDocumentTitle(string $title): void
    {
        $document = $this->document($this->render(pageTitle: $title));

        $this->assertSame($title . ' | Site', $document->getElementsByTagName('title')->item(0)?->textContent);
        $this->assertNoInjectedMarkup($document);
    }

    #[DataProvider('hostileTitleProvider')]
    public function testSiteTitleIsEscapedInTheDocumentTitleAndMenu(string $title): void
    {
        $document = $this->document($this->render(siteTitle: $title));

        $this->assertSame('Page | ' . $title, $document->getElementsByTagName('title')->item(0)?->textContent);
        $this->assertSame($title, trim((new DOMXPath($document))->evaluate('string(//a[contains(@class, "menu-header")])')));
        $this->assertNoInjectedMarkup($document);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileTitleProvider(): iterable
    {
        yield 'script element' => ['<script>alert(1)</script>'];
        yield 'title closing' => ['</title><script>alert(1)</script>'];
        yield 'image with a handler' => ['<img src=x onerror=alert(1)>'];
        yield 'comment' => ['<!--'];
        yield 'quotes' => ['"><script>alert(1)</script>'];
    }

    public function testPlainTitlesAreRenderedAsText(): void
    {
        $html = $this->render(pageTitle: 'Tom & Jerry', siteTitle: 'My "site"');

        $this->assertStringContainsString('<title>Tom &amp; Jerry | My &quot;site&quot;</title>', $html);
    }

    public function testMetadataIsEscapedInAttributes(): void
    {
        $document = $this->document($this->render(metadata: [
            'description' => '"><script>alert(1)</script>',
            'og:title'    => '" onmouseover="alert(1)',
            'refresh'     => '0;url="><script>alert(2)</script>',
        ]));

        $contents = [];
        foreach ($document->getElementsByTagName('meta') as $meta) {
            $contents[$meta->getAttribute('name') ?: $meta->getAttribute('property') ?: $meta->getAttribute('http-equiv')] = $meta->getAttribute('content');
        }

        $this->assertSame('"><script>alert(1)</script>', $contents['description']);
        $this->assertSame('" onmouseover="alert(1)', $contents['og:title']);
        $this->assertSame('0;url="><script>alert(2)</script>', $contents['refresh']);
        $this->assertNoInjectedMarkup($document);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function metadataProvider(): iterable
    {
        yield 'name' => [['description' => 'Text'], '<meta name="description" content="Text">'];
        yield 'open graph property' => [['og:title' => 'Text'], '<meta property="og:title" content="Text">'];
        yield 'charset' => [['charset' => 'utf-8'], '<meta charset="utf-8">'];
        yield 'http-equiv' => [['refresh' => '30'], '<meta http-equiv="refresh" content="30">'];
    }

    /**
     * @param array<string, string> $metadata
     */
    #[DataProvider('metadataProvider')]
    public function testMetadataIsRenderedWithTheRightAttributes(array $metadata, string $expected): void
    {
        $this->assertStringContainsString($expected, $this->render(metadata: $metadata));
    }

    private function document(string $html): DOMDocument
    {
        $this->assertNotSame('', $html);

        $domDocument = new DOMDocument();
        $domDocument->loadHTML($html, LIBXML_NOERROR);

        return $domDocument;
    }

    private function assertNoInjectedMarkup(DOMDocument $domDocument): void
    {
        $domxPath = new DOMXPath($domDocument);

        $this->assertSame(0.0, $domxPath->evaluate('count(//script[normalize-space() != ""])'), 'Inline script injected');
        $this->assertSame(0.0, $domxPath->evaluate('count(//img)'), 'Image injected');
        $this->assertSame(0.0, $domxPath->evaluate('count(//@*[starts-with(name(), "on")])'), 'Event handler injected');
    }

    /**
     * @param array<string, string> $metadata
     */
    private function render(string $pageTitle = 'Page', string $siteTitle = 'Site', array $metadata = []): string
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'site-templates');
        FileSystem::copyDirectory(dirname(__DIR__) . '/Pages/Fixtures/site', $path);

        $site = new Site(
            ['path' => TESTS_TMP_PATH, 'contentPath' => $path, 'metadata' => $metadata, 'title' => $siteTitle],
            $this->app->config(),
            $this->app->getService(PageFactory::class),
            $this->app->getService(PageCollectionFactory::class),
        );

        $page = new Page(['site' => $site, 'path' => FileSystem::joinPaths($path, 'about') . '/'], $this->app);
        $page->set('title', $pageTitle);

        $methods = (require TestInstallation::systemPath() . '/config/views/methods.php')($this->app);

        $view = new View(
            '@fixtures.page',
            ['page' => $page, 'site' => $site, 'app' => $this->app],
            ['' => TestInstallation::rootPath() . '/site/templates', 'fixtures' => __DIR__ . '/Fixtures'],
            $methods,
        );

        return $view->render();
    }
}
