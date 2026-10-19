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
        $tabCollection = new TabCollection(['main' => ['fields' => ['a']], 'side' => []]);

        $this->assertSame(['main', 'side'], $tabCollection->keys());
        $this->assertInstanceOf(Tab::class, $tabCollection->get('main'));
        $this->assertSame('side', $tabCollection->get('side')?->name());
    }

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $tabCollection = new TabCollection([]);

        $this->assertTrue($tabCollection->isAssociative());
        $this->assertSame(Tab::class, $tabCollection->dataType());
    }

    public function testExplicitNameInTheDefinitionWinsOverTheKey(): void
    {
        $tabCollection = new TabCollection(['main' => ['name' => 'other']]);

        $this->assertSame('other', $tabCollection->get('main')?->name());
    }

    public function testListShapedDefinitionsAreRejected(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new TabCollection($this->unchecked([['label' => 'Zero']])));

        $this->assertInstanceOf(LogicException::class, $logicException);
    }

    public function testOrderIsReadFromTheDefinition(): void
    {
        $tabCollection = new TabCollection(['a' => ['order' => 5], 'b' => []]);

        $this->assertSame(5, $tabCollection->get('a')?->order());
        $this->assertSame(PHP_INT_MAX, $tabCollection->get('b')?->order());
    }
}
