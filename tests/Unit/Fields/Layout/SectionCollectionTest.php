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
        $sectionCollection = new SectionCollection(['main' => ['fields' => ['a']], 'side' => []]);

        $this->assertSame(['main', 'side'], $sectionCollection->keys());
        $this->assertInstanceOf(Section::class, $sectionCollection->get('main'));
        $this->assertSame('side', $sectionCollection->get('side')?->name());
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $sectionCollection = new SectionCollection([]);

        $this->assertTrue($sectionCollection->isAssociative());
        $this->assertSame(Section::class, $sectionCollection->dataType());
    }

    public function testExplicitNameInTheDefinitionWinsOverTheKey(): void
    {
        $sectionCollection = new SectionCollection(['main' => ['name' => 'other']]);

        $this->assertSame('other', $sectionCollection->get('main')?->name());
    }

    public function testListShapedDefinitionsAreRejected(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new SectionCollection($this->unchecked([['label' => 'Zero']])));

        $this->assertInstanceOf(LogicException::class, $logicException);
    }

    public function testOrderIsReadFromTheDefinition(): void
    {
        $sectionCollection = new SectionCollection(['a' => ['order' => 5], 'b' => []]);

        $this->assertSame(5, $sectionCollection->get('a')?->order());
        $this->assertSame(PHP_INT_MAX, $sectionCollection->get('b')?->order());
    }
}
