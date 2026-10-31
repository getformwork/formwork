<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\ItemReference;
use Formwork\Images\Isobmff\ItemReferenceBox;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ItemReferenceBox::class)]
#[CoversClass(ItemReference::class)]
final class ItemReferenceBoxTest extends TestCase
{
    public function testReferencesAreParsed(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['cdsc', 3, [1]], ['auxl', 2, [1]]]));

        $this->assertEquals([new ItemReference('cdsc', 3, [1]), new ItemReference('auxl', 2, [1])], $this->references($box));
    }

    public function testReferencesToSeveralItemsAreParsed(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['thmb', 4, [1, 2, 3]]]));

        $this->assertSame([4, 1, 2, 3], $this->references($box)[0]->ids());
    }

    public function testReferencesAreParsedFromTheWiderVersion(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(1, [['cdsc', 70000, [1]]]));

        $this->assertEquals([new ItemReference('cdsc', 70000, [1])], $this->references($box));
    }

    public function testReferencesAreAdded(): void
    {
        $box = new ItemReferenceBox();
        $box->add(ItemReferenceBox::CONTENT_DESCRIBES, 3, 1);

        $this->assertEquals([new ItemReference('cdsc', 3, [1])], $this->references($box));
    }

    public function testReferencesFromAnItemAreRemoved(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['cdsc', 3, [1]], ['auxl', 2, [1]]]));
        $box->removeItem(3);

        $this->assertEquals([new ItemReference('auxl', 2, [1])], $this->references($box));
    }

    public function testReferencesToAnItemAreRemoved(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['cdsc', 3, [1]]]));
        $box->removeItem(1);

        $this->assertSame([], $this->references($box));
    }

    public function testOnlyTheRemovedItemIsDroppedFromAReference(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['thmb', 4, [1, 2]]]));
        $box->removeItem(2);

        $this->assertEquals([new ItemReference('thmb', 4, [1])], $this->references($box));
    }

    public function testRemovingAnUnreferencedItemChangesNothing(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['cdsc', 3, [1]]]));
        $box->removeItem(9);

        $this->assertEquals([new ItemReference('cdsc', 3, [1])], $this->references($box));
    }

    public function testEncodingKeepsTheReferencesUnchanged(): void
    {
        $box = ItemReferenceBox::fromBox($this->box(0, [['cdsc', 3, [1]], ['thmb', 4, [1, 2]]]));

        $this->assertSame(ItemReferenceBox::TYPE, $box->toBox()->type);
        $this->assertEquals($this->references($box), $this->references(ItemReferenceBox::fromBox($box->toBox())));
    }

    public function testWideIdentifiersRaiseTheEncodedVersion(): void
    {
        $box = new ItemReferenceBox();
        $box->add(ItemReferenceBox::CONTENT_DESCRIBES, 70000, 1);

        $this->assertSame(1, ord($box->toBox()->payload[0]));
        $this->assertEquals($this->references($box), $this->references(ItemReferenceBox::fromBox($box->toBox())));
    }

    public function testEmptyBoxesAreEncodedWithTheFirstVersion(): void
    {
        $this->assertSame(0, ord((new ItemReferenceBox())->toBox()->payload[0]));
    }

    /**
     * Build an item reference box with an explicit layout
     *
     * @param list<array{string, int, list<int>}> $references
     */
    private function box(int $version, array $references): Box
    {
        $size = $version === 0 ? 2 : 4;
        $children = [];

        foreach ($references as [$type, $from, $to]) {
            $payload = Bytes::pack($from, $size) . Bytes::pack(count($to), 2);

            foreach ($to as $id) {
                $payload .= Bytes::pack($id, $size);
            }

            $children[] = new Box($type, $payload);
        }

        return new Box(ItemReferenceBox::TYPE, Bytes::fullBox($version) . Box::encodeAll($children));
    }

    /**
     * Read back the references of a box, which only exposes them once encoded
     *
     * @return list<ItemReference>
     */
    private function references(ItemReferenceBox $box): array
    {
        $size = ord($box->toBox()->payload[0]) === 0 ? 2 : 4;
        $children = Box::parseAll(substr($box->toBox()->payload, 4));

        return array_map(static fn(Box $child): ItemReference => ItemReference::fromBox($child, $size), $children);
    }
}
