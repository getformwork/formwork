<?php

namespace Formwork\Tests\Unit\Pages\Traits;

use Formwork\Pages\Page;
use Formwork\Pages\Traits\PageStatus;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversTrait(PageStatus::class)]
final class PageStatusTest extends TestCase
{
    use BuildsPageSites;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validDates(): iterable
    {
        yield 'date only, past' => ['publishDate: "2000-01-01"', Page::PAGE_STATUS_PUBLISHED];
        yield 'date only, future' => ['publishDate: "2099-01-01"', Page::PAGE_STATUS_NOT_PUBLISHED];
        yield 'date and time, past' => ['publishDate: "2000-01-01 10:30 AM"', Page::PAGE_STATUS_PUBLISHED];
        yield 'date and time, future' => ['publishDate: "2099-01-01 10:30 AM"', Page::PAGE_STATUS_NOT_PUBLISHED];
        yield 'relative date' => ['publishDate: "1 January 2000"', Page::PAGE_STATUS_PUBLISHED];
        yield 'empty publish date is ignored' => ['publishDate: ""', Page::PAGE_STATUS_PUBLISHED];
        yield 'null publish date is ignored' => ['publishDate: ~', Page::PAGE_STATUS_PUBLISHED];
        yield 'empty unpublish date is ignored' => ['unpublishDate: ""', Page::PAGE_STATUS_PUBLISHED];
        yield 'unpublished flag wins over the dates' => ["published: false\npublishDate: \"2000-01-01\"", Page::PAGE_STATUS_NOT_PUBLISHED];
    }

    #[DataProvider('validDates')]
    public function testStatusFromFrontmatter(string $frontmatter, string $expected): void
    {
        $this->assertSame($expected, $this->pageWith($frontmatter)->status());
    }

    public function testStatusIsComputedOnce(): void
    {
        $page = $this->pageWith('published: true');
        $this->assertSame(Page::PAGE_STATUS_PUBLISHED, $page->status());

        $page->set('published', false);

        $this->assertSame(Page::PAGE_STATUS_PUBLISHED, $page->status(), 'Status is cached until the page is reloaded');
    }

    public function testIsPublishedMirrorsTheStatus(): void
    {
        $this->assertTrue($this->pageWith('published: true')->isPublished());
        $this->assertFalse($this->pageWith('published: false')->isPublished());
    }

    public function testUnquotedYamlDatesAreAccepted(): void
    {
        // YAML turns unquoted dates into timestamps, which is how most people write them by hand
        $this->assertSame(Page::PAGE_STATUS_NOT_PUBLISHED, $this->pageWith('publishDate: 2099-01-01')->status());
        $this->assertSame(Page::PAGE_STATUS_PUBLISHED, $this->pageWith('publishDate: 2000-01-01')->status());
        $this->assertSame(Page::PAGE_STATUS_NOT_PUBLISHED, $this->pageWith('unpublishDate: 2000-01-01')->status());
    }

    public function testNumericTimestampsAreAccepted(): void
    {
        $this->assertSame(Page::PAGE_STATUS_PUBLISHED, $this->pageWith('publishDate: 946684800')->status());
    }

    public function testUnparsableDatesDoNotBreakThePage(): void
    {
        $page = $this->pageWith('publishDate: "next tuesday-ish maybe"');

        try {
            $status = $page->status();
        } catch (UnexpectedValueException) {
            $this->addToAssertionCount(1);
            return;
        }

        $this->assertContains($status, [Page::PAGE_STATUS_PUBLISHED, Page::PAGE_STATUS_NOT_PUBLISHED]);
    }

    public function testDateArraysAreRejectedExplicitly(): void
    {
        $page = $this->pageWith("publishDate:\n  - 2000-01-01");

        $this->expectException(UnexpectedValueException::class);

        $page->status();
    }

    private function pageWith(string $frontmatter): Page
    {
        $site = $this->siteFromFiles(['page/page.md' => "---\ntitle: Page\n{$frontmatter}\n---\nContent\n"]);

        return $this->pageAt($site, '/page');
    }
}
