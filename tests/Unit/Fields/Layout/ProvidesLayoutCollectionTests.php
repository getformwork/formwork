<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Layout\SectionCollection;
use Formwork\Fields\Layout\Tab;
use Formwork\Fields\Layout\TabCollection;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Tests shared by the collections of sections and tabs of a layout, which behave the same way
 *
 * @mixin TestCase
 */
trait ProvidesLayoutCollectionTests
{
    public function testItemsAreKeyedAndNamedByTheirKey(): void
    {
        $collection = $this->layoutCollection(['main' => ['fields' => ['a']], 'side' => []]);

        $this->assertSame(['main', 'side'], $collection->keys());
        $this->assertInstanceOf($this->layoutItemClass(), $collection->get('main'));
        $this->assertSame('side', $collection->get('side')?->name());
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $collection = $this->layoutCollection([]);

        $this->assertTrue($collection->isAssociative());
        $this->assertSame($this->layoutItemClass(), $collection->dataType());
    }

    public function testExplicitNameInTheDefinitionWinsOverTheKey(): void
    {
        $collection = $this->layoutCollection(['main' => ['name' => 'other']]);

        $this->assertSame('other', $collection->get('main')?->name());
    }

    public function testListShapedDefinitionsAreRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Associative collections cannot be created from non-associative data');

        $this->layoutCollection($this->unchecked([['label' => 'Zero']]));
    }

    public function testOrderIsReadFromTheDefinition(): void
    {
        $collection = $this->layoutCollection(['a' => ['order' => 5], 'b' => []]);

        $this->assertSame(5, $collection->get('a')?->order());
        $this->assertSame(PHP_INT_MAX, $collection->get('b')?->order());
    }

    /**
     * @param array<string, array<string, mixed>> $data
     */
    abstract protected function layoutCollection(array $data): SectionCollection|TabCollection;

    /**
     * @return class-string<Section|Tab>
     */
    abstract protected function layoutItemClass(): string;
}
