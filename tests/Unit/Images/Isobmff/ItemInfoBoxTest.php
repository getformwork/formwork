<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\ItemInfoBox;
use Formwork\Images\Isobmff\ItemInfoEntry;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ItemInfoBox::class)]
#[CoversClass(ItemInfoEntry::class)]
final class ItemInfoBoxTest extends TestCase
{
    public function testEntriesAreParsed(): void
    {
        $box = ItemInfoBox::fromBox($this->box(0, [$this->entry(1, 'av01'), $this->entry(2, 'Exif')]));

        $this->assertSame([1], $box->idsByType('av01'));
        $this->assertSame([2], $box->idsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertSame([], $box->idsByType('mime'));
    }

    public function testEntriesAreParsedFromTheWiderVersion(): void
    {
        $box = ItemInfoBox::fromBox($this->box(1, [$this->entry(1, 'av01')]));

        $this->assertSame([1], $box->idsByType('av01'));
    }

    public function testWideIdentifiersAreParsed(): void
    {
        $box = ItemInfoBox::fromBox($this->box(0, [$this->entry(70000, 'Exif', 3)]));

        $this->assertSame([70000], $box->idsByType(ItemInfoEntry::EXIF_TYPE));
    }

    public function testEntriesOfTheVersionsWithoutATypeAreParsed(): void
    {
        $entry = ItemInfoEntry::fromBox(new Box(ItemInfoEntry::TYPE, Bytes::fullBox(1) . Bytes::pack(4, 2) . Bytes::pack(0, 2) . "name\x00"));

        $this->assertSame(4, $entry->id);
        $this->assertNull($entry->type);
    }

    public function testUnknownChildrenAreIgnored(): void
    {
        $payload = Bytes::fullBox(0) . Bytes::pack(2, 2) . $this->entry(1, 'av01')->encode() . (new Box('free', 'ignored'))->encode();
        $box = ItemInfoBox::fromBox(new Box(ItemInfoBox::TYPE, $payload));

        $this->assertSame([1], $box->idsByType('av01'));
        $this->assertStringNotContainsString('ignored', $box->toBox()->payload);
    }

    /**
     * @param list<int> $ids
     */
    #[DataProvider('nextIdentifierProvider')]
    public function testTheNextIdentifierFollowsTheHighestOne(array $ids, int $expected): void
    {
        $entries = array_map(fn(int $id): Box => $this->entry($id, 'av01'), $ids);

        $this->assertSame($expected, ItemInfoBox::fromBox($this->box(0, $entries))->nextId());
    }

    /**
     * @return iterable<string, array{list<int>, int}>
     */
    public static function nextIdentifierProvider(): iterable
    {
        yield 'no entries' => [[], 1];
        yield 'one entry' => [[1], 2];
        yield 'entries out of order' => [[3, 1, 2], 4];
        yield 'sparse entries' => [[1, 9], 10];
    }

    public function testEntriesAreAddedAndRemoved(): void
    {
        $box = ItemInfoBox::fromBox($this->box(0, [$this->entry(1, 'av01')]));
        $box->add(ItemInfoEntry::create(2, ItemInfoEntry::EXIF_TYPE));

        $this->assertSame([2], $box->idsByType(ItemInfoEntry::EXIF_TYPE));

        $box->remove(2);

        $this->assertSame([], $box->idsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertSame([1], $box->idsByType('av01'));
    }

    public function testCreatedEntriesHaveNoName(): void
    {
        $entry = ItemInfoEntry::create(3, ItemInfoEntry::EXIF_TYPE);
        $parsed = ItemInfoEntry::fromBox($entry->toBox());

        $this->assertSame(3, $parsed->id);
        $this->assertSame(ItemInfoEntry::EXIF_TYPE, $parsed->type);
        $this->assertStringEndsWith("Exif\x00", $entry->toBox()->payload);
    }

    public function testCreatedEntriesUseTheVersionFittingTheirIdentifier(): void
    {
        $this->assertSame(2, ord(ItemInfoEntry::create(3, 'av01')->toBox()->payload[0]));
        $this->assertSame(3, ord(ItemInfoEntry::create(70000, 'av01')->toBox()->payload[0]));
        $this->assertSame(70000, ItemInfoEntry::fromBox(ItemInfoEntry::create(70000, 'av01')->toBox())->id);
    }

    public function testEncodingKeepsTheEntriesUnchanged(): void
    {
        $box = ItemInfoBox::fromBox($this->box(0, [$this->entry(1, 'av01'), $this->entry(2, 'Exif')]));
        $encoded = ItemInfoBox::fromBox($box->toBox());

        $this->assertSame([1], $encoded->idsByType('av01'));
        $this->assertSame([2], $encoded->idsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertSame(ItemInfoBox::TYPE, $box->toBox()->type);
    }

    public function testTheEntryCountIsUpdatedWhenEncoding(): void
    {
        $box = ItemInfoBox::fromBox($this->box(0, [$this->entry(1, 'av01')]));
        $box->add(ItemInfoEntry::create(2, ItemInfoEntry::EXIF_TYPE));

        $count = unpack('n', $box->toBox()->payload, 4);

        $this->assertNotFalse($count);
        $this->assertSame(2, $count[1]);
    }

    /**
     * Build an item information entry
     */
    private function entry(int $id, string $type, int $version = 2): Box
    {
        $payload = Bytes::fullBox($version)
            . Bytes::pack($id, $version >= 3 ? 4 : 2)
            . Bytes::pack(0, 2)
            . $type
            . "\x00";

        return new Box(ItemInfoEntry::TYPE, $payload);
    }

    /**
     * Build an item information box with an explicit layout
     *
     * @param list<Box> $entries
     */
    private function box(int $version, array $entries): Box
    {
        $payload = Bytes::fullBox($version)
            . Bytes::pack(count($entries), $version === 0 ? 2 : 4)
            . Box::encodeAll($entries);

        return new Box(ItemInfoBox::TYPE, $payload);
    }
}
