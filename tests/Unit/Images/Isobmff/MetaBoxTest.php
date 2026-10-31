<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Closure;
use Formwork\Images\Binary\Bytes;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\Extent;
use Formwork\Images\Isobmff\ItemInfoBox;
use Formwork\Images\Isobmff\ItemLocation;
use Formwork\Images\Isobmff\ItemLocationBox;
use Formwork\Images\Isobmff\ItemPropertiesBox;
use Formwork\Images\Isobmff\ItemReferenceBox;
use Formwork\Images\Isobmff\MetaBox;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MetaBox::class)]
final class MetaBoxTest extends TestCase
{
    public function testKnownChildrenAreParsedIntoTheirOwnClasses(): void
    {
        $meta = $this->meta([
            new Box('hdlr', 'handler'),
            (new ItemInfoBox())->toBox(),
            (new ItemLocationBox())->toBox(),
            new Box(ItemPropertiesBox::TYPE, Box::encodeAll([new Box('ipco', '')])),
            (new ItemReferenceBox())->toBox(),
        ]);

        $this->assertInstanceOf(ItemInfoBox::class, $meta->find(ItemInfoBox::class));
        $this->assertInstanceOf(ItemLocationBox::class, $meta->find(ItemLocationBox::class));
        $this->assertInstanceOf(ItemPropertiesBox::class, $meta->find(ItemPropertiesBox::class));
        $this->assertInstanceOf(ItemReferenceBox::class, $meta->find(ItemReferenceBox::class));
    }

    public function testMissingChildrenAreNotFound(): void
    {
        $this->assertNull($this->meta([])->find(ItemInfoBox::class));
    }

    /**
     * @param Closure(MetaBox): mixed $require
     */
    #[DataProvider('requiredChildProvider')]
    public function testRequiredChildrenAreReported(Closure $require, string $type): void
    {
        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage(sprintf('AVIF file does not contain a required "%s" box', $type));

        $require($this->meta([]));
    }

    /**
     * @return iterable<string, array{Closure(MetaBox): mixed, string}>
     */
    public static function requiredChildProvider(): iterable
    {
        yield 'item information' => [static fn(MetaBox $meta) => $meta->require(ItemInfoBox::class), 'iinf'];
        yield 'item location' => [static fn(MetaBox $meta) => $meta->require(ItemLocationBox::class), 'iloc'];
        yield 'item properties' => [static fn(MetaBox $meta) => $meta->require(ItemPropertiesBox::class), 'iprp'];
    }

    public function testChildrenAreAdded(): void
    {
        $meta = $this->meta([new Box('hdlr', 'handler')]);
        $meta->add(new ItemReferenceBox());

        $this->assertInstanceOf(ItemReferenceBox::class, $meta->find(ItemReferenceBox::class));
        $this->assertStringContainsString(ItemReferenceBox::TYPE, $meta->toBox()->payload);
    }

    public function testThePrimaryItemIsRead(): void
    {
        $meta = $this->meta([new Box('pitm', Bytes::fullBox(0) . Bytes::pack(5, 2))]);

        $this->assertSame(5, $meta->primaryItemId());
        $this->assertSame(5, $meta->requirePrimaryItemId());
    }

    public function testTheWiderPrimaryItemIsRead(): void
    {
        $meta = $this->meta([new Box('pitm', Bytes::fullBox(1) . Bytes::pack(70000, 4))]);

        $this->assertSame(70000, $meta->primaryItemId());
    }

    public function testTheMissingPrimaryItemIsReported(): void
    {
        $this->assertNull($this->meta([])->primaryItemId());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not define a primary item');

        $this->meta([])->requirePrimaryItemId();
    }

    public function testTheItemDataIsRead(): void
    {
        $this->assertSame('data', $this->meta([new Box('idat', 'data')])->idat());
        $this->assertNull($this->meta([])->idat());
    }

    public function testTheItemLocationsAndTheItemDataAreReplaced(): void
    {
        $locations = new ItemLocationBox(0, 4, [1 => new ItemLocation(1, 0, 0, [new Extent(8, 4)])]);
        $meta = $this->meta([new Box('hdlr', 'handler'), (new ItemLocationBox())->toBox(), new Box('idat', 'old')]);

        $replaced = $meta->withItemData($locations, 'new');

        $this->assertSame([1], array_keys($replaced->require(ItemLocationBox::class)->items()));
        $this->assertSame('new', $replaced->idat());
        $this->assertSame([], $meta->require(ItemLocationBox::class)->items());
    }

    public function testTheItemDataBoxIsDroppedWhenItBecomesEmpty(): void
    {
        $meta = $this->meta([(new ItemLocationBox())->toBox(), new Box('idat', 'old')]);

        $this->assertNull($meta->withItemData(new ItemLocationBox(), '')->idat());
    }

    public function testEncodingKeepsTheHeaderAndTheChildren(): void
    {
        $children = [new Box('hdlr', 'handler'), new Box('free', 'kept')];
        $box = $this->meta($children)->toBox();

        $this->assertSame(MetaBox::TYPE, $box->type);
        $this->assertSame(Bytes::fullBox(0), substr($box->payload, 0, 4));
        $this->assertEquals($children, Box::parseAll(substr($box->payload, 4)));
    }

    /**
     * Build a meta box holding the given children
     *
     * @param list<Box> $children
     */
    private function meta(array $children): MetaBox
    {
        return MetaBox::fromBox(new Box(MetaBox::TYPE, Bytes::fullBox(0) . Box::encodeAll($children)));
    }
}
