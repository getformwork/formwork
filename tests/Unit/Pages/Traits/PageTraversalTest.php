<?php

namespace Formwork\Tests\Unit\Pages\Traits;

use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(PageTraversal::class)]
final class PageTraversalTest extends TestCase
{
    use BuildsPageSites;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->site = $this->siteFromFiles([
            '1-first/page.md'                 => "---\ntitle: First\n---\n",
            '2-second/page.md'                => "---\ntitle: Second\n---\n",
            '2-second/1-child-a/page.md'      => "---\ntitle: Child A\n---\n",
            '2-second/2-child-b/page.md'      => "---\ntitle: Child B\n---\n",
            '2-second/2-child-b/deep/page.md' => "---\ntitle: Deep\n---\n",
            '3-third/page.md'                 => "---\ntitle: Third\n---\n",
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testParentOfTopLevelPagesIsTheSite(): void
    {
        $this->assertSame($this->site, $this->page('/first')->parent());
        $this->assertTrue($this->page('/first')->hasParent());
    }

    public function testParentOfNestedPagesIsThePageContainingThem(): void
    {
        $this->assertSame($this->page('/second'), $this->page('/second/child-a')->parent());
    }

    public function testSiteHasNoParent(): void
    {
        $this->assertNull($this->site->parent());
        $this->assertFalse($this->site->hasParent());
    }

    public function testIsParentOfAndIsChildOf(): void
    {
        $second = $this->page('/second');
        $child = $this->page('/second/child-a');

        $this->assertTrue($second->isParentOf($child));
        $this->assertTrue($child->isChildOf($second));
        $this->assertFalse($child->isParentOf($second));
        $this->assertFalse($second->isChildOf($child));
        $this->assertTrue($this->site->isParentOf($second));
        $this->assertFalse($this->site->isParentOf($child), 'Only direct parents count');
    }

    public function testChildrenAreDirectDescendantsInFilesystemOrder(): void
    {
        $this->assertSame(['/second/child-a/', '/second/child-b/'], array_values($this->page('/second')->children()->everyItem()->route()->toArray()));
        $this->assertTrue($this->page('/second')->hasChildren());
        $this->assertFalse($this->page('/first')->hasChildren());
    }

    public function testChildrenAreCached(): void
    {
        $second = $this->page('/second');

        $this->assertSame($second->children(), $second->children());
    }

    public function testDescendantsIncludeEveryLevel(): void
    {
        $descendants = array_values($this->page('/second')->descendants()->everyItem()->route()->toArray());

        $this->assertEqualsCanonicalizing(['/second/child-a/', '/second/child-b/', '/second/child-b/deep/'], $descendants);
        $this->assertTrue($this->page('/second')->hasDescendants());
        $this->assertFalse($this->page('/third')->hasDescendants());
    }

    public function testIsDescendantOfFollowsTheWholeChain(): void
    {
        $deep = $this->page('/second/child-b/deep');

        $this->assertTrue($deep->isDescendantOf($this->page('/second/child-b')));
        $this->assertTrue($deep->isDescendantOf($this->page('/second')));
        $this->assertTrue($deep->isDescendantOf($this->site));
        $this->assertFalse($deep->isDescendantOf($this->page('/first')));
        $this->assertFalse($deep->isDescendantOf($deep));
        $this->assertFalse($this->page('/second')->isDescendantOf($deep));
    }

    public function testAncestorsAreOrderedFromTheClosestToTheSite(): void
    {
        $ancestors = $this->page('/second/child-b/deep')->ancestors();

        $this->assertSame(
            [$this->page('/second/child-b'), $this->page('/second'), $this->site],
            $ancestors->values()
        );
    }

    public function testIsAncestorOf(): void
    {
        $second = $this->page('/second');
        $deep = $this->page('/second/child-b/deep');

        $this->assertTrue($second->isAncestorOf($deep));
        $this->assertTrue($this->site->isAncestorOf($deep));
        $this->assertFalse($deep->isAncestorOf($second));
        $this->assertFalse($second->isAncestorOf($second));
        $this->assertFalse($this->page('/first')->isAncestorOf($deep));
    }

    public function testLevelCountsTheAncestorsIncludingTheSite(): void
    {
        $this->assertSame(0, $this->site->level());
        $this->assertSame(1, $this->page('/first')->level());
        $this->assertSame(2, $this->page('/second/child-a')->level());
        $this->assertSame(3, $this->page('/second/child-b/deep')->level());
    }

    public function testSiblingsExcludeThePageItself(): void
    {
        $siblings = $this->page('/second')->siblings();

        $this->assertSame(['/first/', '/third/'], array_values($siblings->everyItem()->route()->toArray()));
        $this->assertTrue($this->page('/second')->hasSiblings());
        $this->assertFalse($this->page('/second')->siblings()->contains($this->page('/second')));
    }

    public function testInclusiveSiblingsContainThePageItself(): void
    {
        $this->assertSame(['/first/', '/second/', '/third/'], array_values($this->page('/second')->inclusiveSiblings()->everyItem()->route()->toArray()));
    }

    public function testOnlyChildrenHaveNoSiblings(): void
    {
        $site = $this->siteFromFiles(['only/page.md' => "---\ntitle: Only\n---\n"]);

        $only = $this->pageAt($site, '/only');
        $this->assertFalse($only->hasSiblings());
        $this->assertNull($only->previousSibling());
        $this->assertNull($only->nextSibling());
    }

    public function testSiblingsAreRelatedOnlyWithinTheSameParent(): void
    {
        $this->assertFalse($this->page('/first')->isSiblingOf($this->page('/second/child-a')));
        $this->assertTrue($this->page('/second/child-a')->isSiblingOf($this->page('/second/child-b')));
        $this->assertFalse($this->page('/first')->isSiblingOf($this->page('/first')));
    }

    public function testIndexIsThePositionAmongSiblings(): void
    {
        $this->assertSame(0, $this->page('/first')->index());
        $this->assertSame(1, $this->page('/second')->index());
        $this->assertSame(2, $this->page('/third')->index());
        $this->assertSame(1, $this->page('/second/child-b')->index());
    }

    public function testNeighbouringSiblings(): void
    {
        $second = $this->page('/second');

        $this->assertSame($this->page('/first'), $second->previousSibling());
        $this->assertSame($this->page('/third'), $second->nextSibling());
    }

    public function testTheFirstPageHasNoPreviousSibling(): void
    {
        $this->assertNull($this->page('/first')->previousSibling());
    }

    public function testTheLastPageHasNoNextSibling(): void
    {
        $this->assertNull($this->page('/third')->nextSibling());
    }

    public function testNestedFirstAndLastPagesHaveNoOutOfRangeSiblings(): void
    {
        $this->assertNull($this->page('/second/child-a')->previousSibling());
        $this->assertNull($this->page('/second/child-b')->nextSibling());
    }

    public function testSiteTraversal(): void
    {
        $this->assertSame(['/first/', '/second/', '/third/'], array_values($this->site->children()->everyItem()->route()->toArray()));
        $this->assertCount(6, $this->site->descendants());
        $this->assertFalse($this->site->hasAncestors());
    }

    private function page(string $route): Page
    {
        return $this->pageAt($this->site, $route);
    }
}
