<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Tab;
use Formwork\Fields\Layout\TabCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TabCollection::class)]
final class TabCollectionTest extends TestCase
{
    public function testTabsAreKeyedAndNamedByTheirKey(): void
    {
        $collection = new TabCollection(['main' => ['fields' => ['a']], 'side' => []]);

        $this->assertSame(['main', 'side'], $collection->keys());
        $this->assertInstanceOf(Tab::class, $collection->get('main'));
        $this->assertSame('side', $collection->get('side')->name());
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $collection = new TabCollection([]);

        $this->assertTrue($collection->isAssociative());
        $this->assertSame(Tab::class, $collection->dataType());
    }

    public function testExplicitNameInTheDefinitionWinsOverTheKey(): void
    {
        $collection = new TabCollection(['main' => ['name' => 'other']]);

        $this->assertSame('other', $collection->get('main')->name());
    }

    public function testListShapedDefinitionsAreRejected(): void
    {
        $this->expectException(LogicException::class);

        new TabCollection([['label' => 'Zero']]);
    }

    public function testOrderIsReadFromTheDefinition(): void
    {
        $collection = new TabCollection(['a' => ['order' => 5], 'b' => []]);

        $this->assertSame(5, $collection->get('a')->order());
        $this->assertSame(PHP_INT_MAX, $collection->get('b')->order());
    }
}
