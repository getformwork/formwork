<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\Extent;
use Formwork\Images\Isobmff\ItemLocation;
use Formwork\Images\Isobmff\ItemLocationBox;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(ItemLocationBox::class)]
#[CoversClass(ItemLocation::class)]
#[CoversClass(Extent::class)]
final class ItemLocationBoxTest extends TestCase
{
    public function testItemsAreParsed(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [
            ['id' => 1, 'extents' => [[100, 20]]],
            ['id' => 7, 'extents' => [[200, 30]]],
        ]));

        $this->assertSame([1, 7], array_keys($box->items()));
        $this->assertEquals([new Extent(100, 20)], $box->find(1)?->extents);
        $this->assertEquals([new Extent(200, 30)], $box->find(7)?->extents);
        $this->assertNull($box->find(2));
    }

    public function testItemsWithSeveralExtentsAreParsed(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [['id' => 1, 'extents' => [[10, 2], [20, 3]]]]));

        $this->assertEquals([new Extent(10, 2), new Extent(20, 3)], $box->find(1)?->extents);
    }

    public function testBaseOffsetsAreAddedToTheExtentOffsets(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [['id' => 1, 'base' => 1000, 'extents' => [[24, 8]]]], baseOffsetSize: 4));

        $this->assertEquals([new Extent(1024, 8)], $box->find(1)?->extents);
    }

    #[DataProvider('constructionMethodProvider')]
    public function testConstructionMethodsTellWhereTheDataIs(int $method, bool $file, bool $idat): void
    {
        $box = ItemLocationBox::fromBox($this->box(1, [['id' => 1, 'method' => $method, 'extents' => [[0, 4]]]]));
        $item = $box->find(1);

        $this->assertNotNull($item);
        $this->assertSame($method, $item->constructionMethod);
        $this->assertSame($file, $item->isInFile());
        $this->assertSame($idat, $item->isInIdat());
    }

    /**
     * @return iterable<string, array{int, bool, bool}>
     */
    public static function constructionMethodProvider(): iterable
    {
        yield 'in the file' => [0, true, false];
        yield 'in the item data box' => [1, false, true];
        yield 'in another item' => [2, false, false];
    }

    public function testDataInOtherFilesIsNeitherInTheFileNorInTheItemDataBox(): void
    {
        $box = ItemLocationBox::fromBox($this->box(1, [['id' => 1, 'reference' => 2, 'extents' => [[0, 4]]]]));
        $item = $box->find(1);

        $this->assertNotNull($item);
        $this->assertFalse($item->isInFile());
        $this->assertFalse($item->isInIdat());
    }

    public function testWideItemIdentifiersAreParsed(): void
    {
        $box = ItemLocationBox::fromBox($this->box(2, [['id' => 70000, 'extents' => [[8, 4]]]]));

        $this->assertSame([70000], array_keys($box->items()));
    }

    public function testExtentIndexesAreParsed(): void
    {
        $box = ItemLocationBox::fromBox($this->box(1, [['id' => 1, 'extents' => [[8, 4, 3]]]], indexSize: 4));
        $item = $box->find(1);

        $this->assertNotNull($item);
        $this->assertSame(3, $item->extents[0]->index);
        $this->assertTrue($item->usesIndex());
    }

    public function testExtentsHaveNoIndexWhenTheBoxDoesNotStoreThem(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [['id' => 1, 'extents' => [[8, 4]]]]));
        $item = $box->find(1);

        $this->assertNotNull($item);
        $this->assertSame(0, $item->extents[0]->index);
        $this->assertFalse($item->usesIndex());
    }

    public function testUnsupportedVersionsAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported iloc box version 3');

        ItemLocationBox::fromBox($this->box(3, []));
    }

    public function testItemsAreAddedAndRemoved(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [['id' => 1, 'extents' => [[8, 4]]]]));
        $box->set(new ItemLocation(5, 0, 0, [new Extent(12, 6)]));

        $this->assertSame([1, 5], array_keys($box->items()));

        $box->remove(1);

        $this->assertSame([5], array_keys($box->items()));

        $box->remove(1);

        $this->assertSame([5], array_keys($box->items()));
    }

    public function testItemsAreReplaced(): void
    {
        $box = ItemLocationBox::fromBox($this->box(0, [['id' => 1, 'extents' => [[8, 4]]]]));
        $replaced = $box->withItems([2 => new ItemLocation(2)]);

        $this->assertSame([1], array_keys($box->items()));
        $this->assertSame([2], array_keys($replaced->items()));
    }

    public function testExtentsAreReplacedAndShifted(): void
    {
        $item = new ItemLocation(1, 0, 0, [new Extent(10, 4, 2)]);
        $replaced = $item->withExtents([new Extent(30, 8)]);

        $this->assertEquals([new Extent(30, 8)], $replaced->extents);
        $this->assertEquals([new Extent(40, 8)], $replaced->shifted(10)->extents);
        $this->assertEquals([new Extent(110, 4, 2)], $item->shifted(100)->extents);
    }

    #[DataProvider('roundTripProvider')]
    public function testEncodingKeepsTheItemsUnchanged(ItemLocationBox $box): void
    {
        $encoded = ItemLocationBox::fromBox($box->toBox());

        $this->assertEquals($box->items(), $encoded->items());
    }

    /**
     * @return iterable<string, array{ItemLocationBox}>
     */
    public static function roundTripProvider(): iterable
    {
        yield 'no items' => [new ItemLocationBox()];
        yield 'one item' => [new ItemLocationBox(0, 4, [1 => new ItemLocation(1, 0, 0, [new Extent(100, 20)])])];
        yield 'several extents' => [new ItemLocationBox(0, 4, [1 => new ItemLocation(1, 0, 0, [new Extent(10, 2), new Extent(20, 3)])])];
        yield 'item in the item data box' => [new ItemLocationBox(1, 4, [1 => new ItemLocation(1, 1, 0, [new Extent(0, 20)])])];
        yield 'extent indexes' => [new ItemLocationBox(1, 4, [1 => new ItemLocation(1, 0, 0, [new Extent(0, 20, 3)])])];
        yield 'wide identifiers' => [new ItemLocationBox(2, 4, [70000 => new ItemLocation(70000, 0, 0, [new Extent(0, 20)])])];
        yield '64-bit offsets' => [new ItemLocationBox(0, 8, [1 => new ItemLocation(1, 0, 0, [new Extent(5000000000, 20)])])];
    }

    public function testTheBoxIsEncodedWithItsOwnType(): void
    {
        $this->assertSame(ItemLocationBox::TYPE, (new ItemLocationBox())->toBox()->type);
    }

    public function testWideIdentifiersRaiseTheEncodedVersion(): void
    {
        $box = new ItemLocationBox(0, 4, [70000 => new ItemLocation(70000, 0, 0, [new Extent(0, 20)])]);

        $this->assertSame(2, ord($box->toBox()->payload[0]));
    }

    public function testExtentIndexesRaiseTheEncodedVersion(): void
    {
        $box = new ItemLocationBox(0, 4, [1 => new ItemLocation(1, 0, 0, [new Extent(10, 4, 7)])]);
        $encoded = $box->toBox();

        $this->assertSame(1, ord($encoded->payload[0]));
        $this->assertSame(7, ItemLocationBox::fromBox($encoded)->find(1)?->extents[0]->index);
    }

    /**
     * Build an item location box with an explicit layout
     *
     * @param list<array{id: int, extents: list<array{int, int, 2?: int}>, method?: int, reference?: int, base?: int}> $items
     */
    private function box(int $version, array $items, int $offsetSize = 4, int $lengthSize = 4, int $baseOffsetSize = 0, int $indexSize = 0): Box
    {
        $wide = $version < 2 ? 2 : 4;

        $payload = Bytes::fullBox($version)
            . Bytes::pack(($offsetSize << 12) | ($lengthSize << 8) | ($baseOffsetSize << 4) | $indexSize, 2)
            . Bytes::pack(count($items), $wide);

        foreach ($items as $item) {
            $payload .= Bytes::pack($item['id'], $wide)
                . ($version > 0 ? Bytes::pack($item['method'] ?? 0, 2) : '')
                . Bytes::pack($item['reference'] ?? 0, 2)
                . Bytes::pack($item['base'] ?? 0, $baseOffsetSize)
                . Bytes::pack(count($item['extents']), 2);

            foreach ($item['extents'] as $extent) {
                $payload .= Bytes::pack($extent[2] ?? 0, $version > 0 ? $indexSize : 0)
                    . Bytes::pack($extent[0], $offsetSize)
                    . Bytes::pack($extent[1], $lengthSize);
            }
        }

        return new Box(ItemLocationBox::TYPE, $payload);
    }
}
