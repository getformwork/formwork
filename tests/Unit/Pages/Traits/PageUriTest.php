<?php

namespace Formwork\Tests\Unit\Pages\Traits;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Languages\Languages;
use Formwork\Pages\Traits\PageUri;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\BuildsPageSites;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversTrait(PageUri::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PageUriTest extends TestCase
{
    use BuildsPageSites;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->siteFromFiles([
            'blog/page.md'      => "---\ntitle: Blog\n---\n",
            'blog/post/page.md' => "---\ntitle: Post\n---\n",
            'aliased/page.md'   => "---\ntitle: Aliased\ncanonicalRoute: /canonical-route/\n---\n",
        ]);
    }

    public function testUriIsTheRouteUnderTheRequestRoot(): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');

        $this->assertSame($root . '/blog/', $this->pageAt($this->site, '/blog')->uri('', false));
        $this->assertSame($root . '/blog/post/', $this->pageAt($this->site, '/blog/post')->uri('', false));
    }

    public function testCanonicalRouteWinsOverTheRoute(): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');

        $this->assertSame($root . '/canonical-route/', $this->pageAt($this->site, '/aliased')->uri('', false));
    }

    public function testPathIsAppendedToTheRoute(): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');
        $page = $this->pageAt($this->site, '/blog');

        $this->assertSame($root . '/blog/image.png', $page->uri('image.png', false));
        $this->assertSame($root . '/blog/files/image.png', $page->uri('files/image.png', false));
        $this->assertSame($root . '/blog/image.png', $page->uri('/image.png', false));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversalPathsProvider(): iterable
    {
        yield 'parent directory' => ['../secret'];
        yield 'several parents' => ['../../../../secret'];
        yield 'nested parents' => ['a/../../../secret'];
        yield 'backslashes' => ['..\..\secret'];
    }

    #[DataProvider('traversalPathsProvider')]
    public function testPathsCannotClimbAboveTheApplicationRoot(string $path): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');

        $uri = $this->pageAt($this->site, '/blog/post')->uri($path, false);

        $this->assertStringStartsWith($root . '/', $uri);
        $this->assertStringNotContainsString('..', $uri);
    }

    /**
     * @param array{available?: array<string>, default?: ?string, current?: string, preferred?: string} $languages
     */
    #[DataProvider('languagePrefixProvider')]
    public function testLanguagePrefixIsAddedOnlyWhenNeeded(array $languages, bool|string $includeLanguage, string $expected): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');
        (new ReflectionProperty(Site::class, 'languages'))->setValue($this->site, new Languages(['available' => ['en' => 'en', 'it' => 'it'], 'default' => 'en', ...$languages]));

        $this->assertSame($root . $expected, $this->pageAt($this->site, '/blog')->uri(includeLanguage: $includeLanguage));
    }

    /**
     * @return iterable<string, array{array{available?: array<string>, default?: ?string, current?: string, preferred?: string}, bool|string, string}>
     */
    public static function languagePrefixProvider(): iterable
    {
        yield 'default language' => [[], true, '/blog/'];
        yield 'other current language' => [['current' => 'it'], true, '/it/blog/'];
        yield 'default language preferred' => [['preferred' => 'en'], true, '/blog/'];
        // The default language is made explicit when the visitor prefers another one
        yield 'other language preferred' => [['preferred' => 'it'], true, '/en/blog/'];
        yield 'given language' => [[], 'it', '/it/blog/'];
        yield 'language excluded' => [['current' => 'it'], false, '/blog/'];
        yield 'no languages' => [['available' => [], 'default' => null], true, '/blog/'];
    }

    public function testSiteUriUsesTheRoot(): void
    {
        $root = App::instance()->request()->root();

        $this->assertSame(rtrim($root, '/') . '/', rtrim($this->site->uri('', false), '/') . '/');
    }
}
