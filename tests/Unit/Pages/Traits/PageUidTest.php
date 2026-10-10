<?php

namespace Formwork\Tests\Unit\Pages\Traits;

use Formwork\Cms\Site;
use Formwork\Pages\Traits\PageUid;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(PageUid::class)]
final class PageUidTest extends TestCase
{
    use BuildsPageSites;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->site = $this->siteFromFiles([
            'one/page.md'        => "---\ntitle: One\n---\n",
            'two/page.md'        => "---\ntitle: Two\n---\n",
            'two/nested/page.md' => "---\ntitle: Nested\n---\n",
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testUidHasTheExpectedShape(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}(-[0-9a-f]{8}){3}$/', $this->pageAt($this->site, '/one')->uid());
    }

    public function testUidIsDerivedFromTheContentRelativePath(): void
    {
        $page = $this->pageAt($this->site, '/one');

        $expected = implode('-', str_split(substr(hash('sha256', (string) $page->contentRelativePath()), 0, 32), 8));

        $this->assertSame($expected, $page->uid());
    }

    public function testUidIsStableAcrossInstancesOfTheSamePage(): void
    {
        $first = $this->pageAt($this->site, '/one')->uid();
        $again = $this->pageAt($this->siteFromPath((string) $this->site->contentPath()), '/one')->uid();

        $this->assertSame($first, $again);
    }

    public function testUidIsCached(): void
    {
        $page = $this->pageAt($this->site, '/one');

        $this->assertSame($page->uid(), $page->uid());
    }

    public function testDifferentPagesHaveDifferentUids(): void
    {
        $uids = [
            $this->pageAt($this->site, '/one')->uid(),
            $this->pageAt($this->site, '/two')->uid(),
            $this->pageAt($this->site, '/two/nested')->uid(),
        ];

        $this->assertSame($uids, array_values(array_unique($uids)));
    }

    public function testTheSiteHasItsOwnUid(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}(-[0-9a-f]{8}){3}$/', $this->site->uid());
        $this->assertNotSame($this->site->uid(), $this->pageAt($this->site, '/one')->uid());
    }
}
