<?php

namespace Formwork\Tests\Unit\Pages\Traits;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Traits\PageUri;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversTrait(PageUri::class)]
final class PageUriTest extends TestCase
{
    use BuildsPageSites;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->site = $this->siteFromFiles([
            'blog/page.md'      => "---\ntitle: Blog\n---\n",
            'blog/post/page.md' => "---\ntitle: Post\n---\n",
            'aliased/page.md'   => "---\ntitle: Aliased\ncanonicalRoute: /canonical-route/\n---\n",
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
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
    public static function traversalPaths(): iterable
    {
        yield 'parent directory' => ['../secret'];
        yield 'several parents' => ['../../../../secret'];
        yield 'nested parents' => ['a/../../../secret'];
        yield 'backslashes' => ['..\..\secret'];
    }

    #[DataProvider('traversalPaths')]
    public function testPathsCannotClimbAboveTheApplicationRoot(string $path): void
    {
        $root = rtrim(App::instance()->request()->root(), '/');

        $uri = $this->pageAt($this->site, '/blog/post')->uri($path, false);

        $this->assertStringStartsWith($root . '/', $uri);
        $this->assertStringNotContainsString('..', $uri);
    }

    public function testSiteUriUsesTheRoot(): void
    {
        $root = App::instance()->request()->root();

        $this->assertSame(rtrim($root, '/') . '/', rtrim($this->site->uri('', false), '/') . '/');
    }
}
