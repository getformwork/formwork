<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\ItemPropertiesBox;
use Formwork\Images\Isobmff\PropertyAssociation;
use Formwork\Images\Isobmff\PropertyAssociationBox;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ItemPropertiesBox::class)]
#[CoversClass(PropertyAssociationBox::class)]
#[CoversClass(PropertyAssociation::class)]
final class ItemPropertiesBoxTest extends TestCase
{
    public function testPropertiesAreParsed(): void
    {
        $box = $this->properties([new Box('ispe', 'size'), new Box('colr', 'profile')], [1 => [1, 2]]);

        $this->assertCount(2, $box->all());
        $this->assertSame('ispe', $box->all()[0]->type);
        $this->assertSame('colr', $box->all()[1]->type);
    }

    public function testPropertiesAreFoundByItem(): void
    {
        $box = $this->properties([new Box('ispe', 'size'), new Box('colr', 'profile'), new Box('auxC', 'alpha')], [1 => [1, 2], 2 => [3]]);

        $this->assertSame(['ispe', 'colr'], array_column($box->forItem(1), 'type'));
        $this->assertSame(['auxC'], array_column($box->forItem(2), 'type'));
        $this->assertSame([], $box->forItem(3));
    }

    public function testAssociationsWithMissingPropertiesAreIgnored(): void
    {
        $box = $this->properties([new Box('ispe', 'size')], [1 => [1, 9]]);

        $this->assertSame(['ispe'], array_column($box->forItem(1), 'type'));
    }

    public function testUnknownChildrenAreKept(): void
    {
        $container = new Box('ipco', Box::encodeAll([new Box('ispe', 'size')]));
        $other = new Box('free', 'kept');
        $box = ItemPropertiesBox::fromBox(new Box(ItemPropertiesBox::TYPE, Box::encodeAll([$container, $other])));

        $this->assertStringContainsString('kept', $box->toBox()->payload);
    }

    public function testPropertiesAreAddedAndAssociatedWithAnItem(): void
    {
        $box = $this->properties([new Box('ispe', 'size')], [1 => [1]]);
        $box->add(new Box('colr', 'profile'), 1);

        $this->assertCount(2, $box->all());
        $this->assertSame(['ispe', 'colr'], array_column($box->forItem(1), 'type'));
    }

    public function testPropertiesAreAddedToBoxesWithoutAssociations(): void
    {
        $container = new Box('ipco', Box::encodeAll([new Box('ispe', 'size')]));
        $box = ItemPropertiesBox::fromBox(new Box(ItemPropertiesBox::TYPE, Box::encodeAll([$container])));
        $box->add(new Box('colr', 'profile'), 1);

        $this->assertSame(['colr'], array_column($this->reread($box)->forItem(1), 'type'));
    }

    public function testPropertiesAreRemovedByPredicate(): void
    {
        $box = $this->properties([new Box('ispe', 'size'), new Box('colr', 'profile')], [1 => [1, 2]]);

        $this->assertSame(1, $box->removeWhere(static fn(Box $property): bool => $property->type === 'colr'));
        $this->assertSame(['ispe'], array_column($box->all(), 'type'));
        $this->assertSame(0, $box->removeWhere(static fn(Box $property): bool => $property->type === 'colr'));
    }

    public function testRemovingPropertiesRenumbersTheFollowingAssociations(): void
    {
        $box = $this->properties([new Box('colr', 'profile'), new Box('ispe', 'size'), new Box('auxC', 'alpha')], [1 => [1, 2], 2 => [2, 3]]);
        $box->removeWhere(static fn(Box $property): bool => $property->type === 'colr');

        $reread = $this->reread($box);

        $this->assertSame(['ispe'], array_column($reread->forItem(1), 'type'));
        $this->assertSame(['ispe', 'auxC'], array_column($reread->forItem(2), 'type'));
    }

    public function testSeveralPropertiesAreRemovedAtOnce(): void
    {
        $box = $this->properties([new Box('colr', 'first'), new Box('ispe', 'size'), new Box('colr', 'second')], [1 => [1, 2, 3]]);

        $this->assertSame(2, $box->removeWhere(static fn(Box $property): bool => $property->type === 'colr'));
        $this->assertSame(['ispe'], array_column($this->reread($box)->forItem(1), 'type'));
    }

    public function testEssentialAssociationsAreKept(): void
    {
        $associations = new PropertyAssociationBox();
        $associations->associate(1, 1, essential: true);
        $associations->associate(1, 2);

        $parsed = PropertyAssociationBox::fromBox($associations->toBox());
        $payload = $parsed->toBox()->payload;

        $this->assertSame([1, 2], $parsed->indexesFor(1));
        $this->assertSame(0x81, ord($payload[11]), 'The first property is marked as essential');
        $this->assertSame(0x02, ord($payload[12]), 'The second property is not');
    }

    public function testAssociationsAreParsedFromTheWiderVersion(): void
    {
        $payload = Bytes::fullBox(1) . Bytes::pack(1, 4) . Bytes::pack(70000, 4) . Bytes::pack(1, 1) . Bytes::pack(2, 1);
        $box = PropertyAssociationBox::fromBox(new Box(PropertyAssociationBox::TYPE, $payload));

        $this->assertSame([2], $box->indexesFor(70000));
        $this->assertSame(1, ord($box->toBox()->payload[0]));
    }

    public function testWideIdentifiersRaiseTheEncodedVersion(): void
    {
        $box = new PropertyAssociationBox();
        $box->associate(70000, 1);

        $this->assertSame(1, ord($box->toBox()->payload[0]));
        $this->assertSame([1], PropertyAssociationBox::fromBox($box->toBox())->indexesFor(70000));
    }

    #[DataProvider('wideIndexProvider')]
    public function testIndexesAreStoredOnTwoBytesWhenNeeded(int $index, bool $wide): void
    {
        $box = new PropertyAssociationBox();
        $box->associate(1, $index);

        $this->assertSame($wide, (ord($box->toBox()->payload[3]) & 1) === 1);
        $this->assertSame([$index], PropertyAssociationBox::fromBox($box->toBox())->indexesFor(1));
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function wideIndexProvider(): iterable
    {
        yield 'the highest narrow index' => [127, false];
        yield 'the lowest wide index' => [128, true];
    }

    public function testTheWideIndexFlagIsKeptOnceSet(): void
    {
        $payload = Bytes::fullBox(0, 1) . Bytes::pack(1, 4) . Bytes::pack(1, 2) . Bytes::pack(1, 1) . Bytes::pack(2, 2);
        $box = PropertyAssociationBox::fromBox(new Box(PropertyAssociationBox::TYPE, $payload));

        $this->assertSame([2], $box->indexesFor(1));
        $this->assertSame($payload, $box->toBox()->payload);
    }

    public function testRemovingAPropertyFromAnItemWithoutAssociationsChangesNothing(): void
    {
        $box = new PropertyAssociationBox();
        $box->removeProperty(1);

        $this->assertSame([], $box->indexesFor(1));
        $this->assertSame(PropertyAssociationBox::TYPE, $box->toBox()->type);
    }

    public function testEncodingKeepsThePropertiesUnchanged(): void
    {
        $box = $this->properties([new Box('ispe', 'size'), new Box('colr', 'profile')], [1 => [1, 2]]);
        $reread = $this->reread($box);

        $this->assertSame(ItemPropertiesBox::TYPE, $box->toBox()->type);
        $this->assertEquals($box->all(), $reread->all());
        $this->assertEquals($box->forItem(1), $reread->forItem(1));
    }

    /**
     * Build an item properties box holding the given properties and associations
     *
     * @param list<Box>             $properties
     * @param array<int, list<int>> $associations
     */
    private function properties(array $properties, array $associations): ItemPropertiesBox
    {
        $container = new Box('ipco', Box::encodeAll($properties));
        $box = new PropertyAssociationBox();

        foreach ($associations as $itemId => $indexes) {
            foreach ($indexes as $index) {
                $box->associate($itemId, $index);
            }
        }

        return ItemPropertiesBox::fromBox(new Box(ItemPropertiesBox::TYPE, Box::encodeAll([$container, $box])));
    }

    /**
     * Encode a box and parse it again, to read it back the way a decoder would
     */
    private function reread(ItemPropertiesBox $box): ItemPropertiesBox
    {
        return ItemPropertiesBox::fromBox($box->toBox());
    }
}
