<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Layout;
use Formwork\Fields\Layout\Section;
use Formwork\Fields\Layout\Tab;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Layout::class)]
final class LayoutTest extends TestCase
{
    public function testTypeIsSections(): void
    {
        $this->assertSame('sections', $this->layout([])->type());
    }

    public function testEmptyLayoutHasNoSectionsOrTabs(): void
    {
        $layout = $this->layout([]);

        $this->assertCount(0, $layout->sections());
        $this->assertCount(0, $layout->tabs());
    }

    public function testSectionsAreBuiltFromTheDefinitionAndNamedAfterTheirKeys(): void
    {
        $layout = $this->layout(['sections' => [
            'main'    => ['label' => 'Main', 'fields' => ['a']],
            'sidebar' => ['fields' => ['b']],
        ]]);

        $this->assertSame(['main', 'sidebar'], $layout->sections()->keys());
        $this->assertContainsOnlyInstancesOf(Section::class, $layout->sections());
        $this->assertSame('main', $layout->sections()->get('main')->name());
        $this->assertSame(['b'], $layout->sections()->get('sidebar')->get('fields'));
    }

    public function testSectionsAreSortedByOrderAndKeepDefinitionOrderOtherwise(): void
    {
        $layout = $this->layout(['sections' => [
            'late'      => ['order' => 10],
            'unordered' => [],
            'early'     => ['order' => 1],
            'another'   => [],
        ]]);

        $this->assertSame(['early', 'late', 'unordered', 'another'], $layout->sections()->keys());
    }

    public function testSectionsAreTranslated(): void
    {
        $layout = $this->layout(['sections' => ['main' => ['label' => '{{greeting}}']]]);

        $this->assertSame('Hello', $layout->sections()->get('main')->label());
    }

    public function testSectionsAreBuiltOnce(): void
    {
        $layout = $this->layout(['sections' => ['main' => []]]);

        $this->assertSame($layout->sections(), $layout->sections());
    }

    public function testTabsAreBuiltFromTheDefinitionAndSorted(): void
    {
        $layout = $this->layout(['tabs' => [
            'seo'     => ['order' => 2, 'label' => 'SEO'],
            'content' => ['order' => 1],
        ]]);

        $this->assertSame(['content', 'seo'], $layout->tabs()->keys());
        $this->assertContainsOnlyInstancesOf(Tab::class, $layout->tabs());
        $this->assertSame('SEO', $layout->tabs()->get('seo')->label());
        $this->assertSame('content', $layout->tabs()->get('content')->label());
    }

    public function testTabsAreTranslatedAndBuiltOnce(): void
    {
        $layout = $this->layout(['tabs' => ['a' => ['label' => '{{greeting}}']]]);

        $this->assertSame('Hello', $layout->tabs()->get('a')->label());
        $this->assertSame($layout->tabs(), $layout->tabs());
    }

    public function testSectionsAndTabsAreIndependent(): void
    {
        $layout = $this->layout(['sections' => ['s' => []], 'tabs' => ['t' => []]]);

        $this->assertSame(['s'], $layout->sections()->keys());
        $this->assertSame(['t'], $layout->tabs()->keys());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function layout(array $data): Layout
    {
        return new Layout($data, new Translation('en', ['greeting' => 'Hello']));
    }
}
