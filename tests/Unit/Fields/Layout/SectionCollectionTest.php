<?php

namespace Formwork\Tests\Unit\Fields\Layout;

use Formwork\Fields\Layout\Section;
use Formwork\Fields\Layout\SectionCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SectionCollection::class)]
final class SectionCollectionTest extends TestCase
{
    public function testSectionsAreKeyedAndNamedByTheirKey(): void
    {
        $collection = new SectionCollection(['main' => ['fields' => ['a']], 'side' => []]);

        $this->assertSame(['main', 'side'], $collection->keys());
        $this->assertInstanceOf(Section::class, $collection->get('main'));
        $this->assertSame('side', $collection->get('side')->name());
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $collection = new SectionCollection([]);

        $this->assertTrue($collection->isAssociative());
        $this->assertSame(Section::class, $collection->dataType());
    }

    public function testExplicitNameInTheDefinitionWinsOverTheKey(): void
    {
        $collection = new SectionCollection(['main' => ['name' => 'other']]);

        $this->assertSame('other', $collection->get('main')->name());
    }

    public function testListShapedDefinitionsAreRejected(): void
    {
        $this->expectException(LogicException::class);

        new SectionCollection([['label' => 'Zero']]);
    }

    public function testOrderIsReadFromTheDefinition(): void
    {
        $collection = new SectionCollection(['a' => ['order' => 5], 'b' => []]);

        $this->assertSame(5, $collection->get('a')->order());
        $this->assertSame(PHP_INT_MAX, $collection->get('b')->order());
    }
}
