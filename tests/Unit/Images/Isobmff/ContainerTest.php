<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Isobmff\Container;
use Formwork\Images\Isobmff\DataArea;
use Formwork\Images\Isobmff\ImageSequence;
use Formwork\Images\Isobmff\ItemInfoBox;
use Formwork\Images\Isobmff\ItemInfoEntry;
use Formwork\Images\Isobmff\ItemLocationBox;
use Formwork\Images\Isobmff\ItemReferenceBox;
use Formwork\Images\Isobmff\MetaBox;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFileFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Container::class)]
#[CoversClass(DataArea::class)]
final class ContainerTest extends TestCase
{
    public function testTheMajorBrandIsRead(): void
    {
        $this->assertSame('avif', $this->parse(AvifFileFixture::create())->majorBrand());
        $this->assertSame('avis', $this->parse(AvifFileFixture::create()->asSequence())->majorBrand());
    }

    public function testTheMetadataIsFound(): void
    {
        $container = $this->parse(AvifFileFixture::create());

        $this->assertInstanceOf(MetaBox::class, $container->meta());
        $this->assertSame($container->meta(), $container->requireMeta());
    }

    public function testTheMissingMetadataIsReported(): void
    {
        $container = Container::parse(new AvifDecoder(), AvifFileFixture::box('ftyp', 'avif' . pack('N', 0) . 'avif'));

        $this->assertNull($container->meta());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not contain a meta box');

        $container->requireMeta();
    }

    public function testItemsAreFoundByType(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withAlpha()->withExif('TIFF'));

        $this->assertSame([AvifFileFixture::PRIMARY_ID, AvifFileFixture::ALPHA_ID], $container->itemIdsByType('av01'));
        $this->assertSame([AvifFileFixture::EXIF_ID], $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertSame([], $container->itemIdsByType('mime'));
    }

    public function testItemsHaveNoTypeWithoutTheItemInformation(): void
    {
        $container = Container::parse(new AvifDecoder(), AvifFileFixture::box('ftyp', 'avif' . pack('N', 0) . 'avif'));

        $this->assertSame([], $container->itemIdsByType('av01'));
    }

    public function testTheDataOfItemsStoredInTheFileIsRead(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withAlpha());

        $this->assertSame(AvifFileFixture::PRIMARY_DATA, $container->itemData(AvifFileFixture::PRIMARY_ID));
        $this->assertSame(AvifFileFixture::ALPHA_DATA, $container->itemData(AvifFileFixture::ALPHA_ID));
    }

    public function testTheDataOfItemsStoredInTheItemDataBoxIsRead(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withRawExif('in the item data box')->withLocationVersion(1)->withItemDataBox());

        $this->assertSame('in the item data box', $container->itemData(AvifFileFixture::EXIF_ID));
    }

    public function testTheDataOfUnknownItemsIsNotRead(): void
    {
        $this->assertNull($this->parse(AvifFileFixture::create())->itemData(99));
    }

    public function testTheDataOfItemsStoredInOtherFilesIsNotRead(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withRawExif('elsewhere')->withDataReference(AvifFileFixture::EXIF_ID));

        $this->assertNull($container->itemData(AvifFileFixture::EXIF_ID));
    }

    public function testExtentsWithoutALengthAreNotSupported(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withRawExif('data')->withExtentOverride(AvifFileFixture::EXIF_ID, length: 0));

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('Item extents without an explicit length are not supported');

        $container->itemData(AvifFileFixture::EXIF_ID);
    }

    public function testExtentsBeyondTheAvailableDataAreRejected(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withRawExif('data')->withExtentOverride(AvifFileFixture::EXIF_ID, offset: 100000));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Item extent exceeds the available data');

        $container->itemData(AvifFileFixture::EXIF_ID);
    }

    public function testItemsAreAdded(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');

        $this->assertSame(2, $id);
        $this->assertSame('added', $container->itemData($id));
        $this->assertSame([$id], $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
    }

    public function testAddedItemsAreWrittenToTheFile(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame('added', $rewritten->itemData($id));
        $this->assertSame(AvifFileFixture::PRIMARY_DATA, $rewritten->itemData(AvifFileFixture::PRIMARY_ID));
    }

    public function testItemsWithoutDataHaveNoExtents(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, '');

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame('', $rewritten->itemData($id));
        $this->assertSame([], $rewritten->requireMeta()->require(ItemLocationBox::class)->find($id)?->extents);
    }

    public function testReferencesAreAddedToFilesWithoutThem(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $container->addReference(ItemReferenceBox::CONTENT_DESCRIBES, 2, AvifFileFixture::PRIMARY_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertInstanceOf(ItemReferenceBox::class, $rewritten->requireMeta()->find(ItemReferenceBox::class));
        $this->assertStringContainsString(ItemReferenceBox::CONTENT_DESCRIBES, $container->toString());
    }

    public function testReferencesAreAddedToTheExistingBox(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withAlpha());
        $container->addReference(ItemReferenceBox::CONTENT_DESCRIBES, 4, AvifFileFixture::PRIMARY_ID);

        $this->assertSame(1, substr_count($container->toString(), ItemReferenceBox::TYPE));
        $this->assertStringContainsString('auxl', $container->toString());
    }

    public function testItemsAreRemovedWithTheirDataAndReferences(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withAlpha()->withRawExif('metadata'));
        $container->removeItem(AvifFileFixture::EXIF_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame([], $rewritten->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertNull($rewritten->itemData(AvifFileFixture::EXIF_ID));
        $this->assertStringNotContainsString('metadata', $rewritten->toString());
        $this->assertStringNotContainsString(ItemReferenceBox::CONTENT_DESCRIBES, $rewritten->toString());
    }

    public function testRemovedItemsDoNotKeepTheirAddedData(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');
        $container->removeItem($id);

        $this->assertStringNotContainsString('added', $container->toString());
    }

    #[DataProvider('layoutProvider')]
    public function testTheDataOfTheItemsSurvivesTheRewriting(AvifFileFixture $file): void
    {
        $rewritten = Container::parse(new AvifDecoder(), $this->parse($file)->toString());

        $this->assertSame(AvifFileFixture::PRIMARY_DATA, $rewritten->itemData(AvifFileFixture::PRIMARY_ID));
        $this->assertSame(AvifFileFixture::ALPHA_DATA, $rewritten->itemData(AvifFileFixture::ALPHA_ID));
        $this->assertSame('metadata', $rewritten->itemData(AvifFileFixture::EXIF_ID));
    }

    /**
     * @return iterable<string, array{AvifFileFixture}>
     */
    public static function layoutProvider(): iterable
    {
        $file = static fn(): AvifFileFixture => AvifFileFixture::create()->withAlpha()->withRawExif('metadata');

        yield 'media data after the metadata' => [$file()];
        yield 'media data before the metadata' => [$file()->withMediaDataFirst()];
        yield 'media data with a 64-bit size' => [$file()->withLargeMediaDataBox()];
        yield 'media data without a size' => [$file()->withUnsizedMediaDataBox()];
        yield 'item data box' => [$file()->withLocationVersion(1)->withItemDataBox()];
        yield 'base offsets' => [$file()->withLocationVersion(1, 4)];
        yield 'wide item location' => [$file()->withLocationVersion(2)];
    }

    public function testTheRewrittenFileKeepsTheOtherBoxes(): void
    {
        $rewritten = $this->parse(AvifFileFixture::create()->withFreeSpace())->toString();

        $this->assertStringContainsString('free-space', $rewritten);
        $this->assertSame('avif', Container::parse(new AvifDecoder(), $rewritten)->majorBrand());
        $this->assertSame(AvifFileFixture::PRIMARY_DATA, Container::parse(new AvifDecoder(), $rewritten)->itemData(AvifFileFixture::PRIMARY_ID));
    }

    public function testTheRewritingIsIdempotent(): void
    {
        $once = $this->parse(AvifFileFixture::create()->withAlpha()->withRawExif('metadata'))->toString();
        $twice = Container::parse(new AvifDecoder(), $once)->toString();

        $this->assertSame($once, $twice);
    }

    public function testImageSequencesExposeTheirTimeline(): void
    {
        $sequence = $this->parse(AvifFileFixture::create()->withFrames(6, 2))->imageSequence();

        $this->assertInstanceOf(ImageSequence::class, $sequence);
        $this->assertSame(6, $sequence->frameCount);
        $this->assertSame(2, $sequence->playCount);
    }

    public function testStillImagesHaveNoTimeline(): void
    {
        $this->assertNull($this->parse(AvifFileFixture::create())->imageSequence());
    }

    public function testImageSequencesCannotBeRewritten(): void
    {
        $container = $this->parse(AvifFileFixture::create()->asSequence());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('Modifying AVIF image sequences is not supported');

        $container->toString();
    }

    public function testItemsPointingToOtherFilesAreLeftUntouched(): void
    {
        $container = $this->parse(AvifFileFixture::create()->withRawExif('elsewhere')->withDataReference(AvifFileFixture::EXIF_ID));
        $before = $container->requireMeta()->require(ItemLocationBox::class)->find(AvifFileFixture::EXIF_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertEquals($before, $rewritten->requireMeta()->require(ItemLocationBox::class)->find(AvifFileFixture::EXIF_ID));
    }

    public function testTheItemInformationIsRequiredToAddItems(): void
    {
        $container = $this->parse(AvifFileFixture::create());
        $container->requireMeta()->require(ItemInfoBox::class)->remove(AvifFileFixture::PRIMARY_ID);

        $this->assertSame(1, $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added'));
    }

    private function parse(AvifFileFixture $file): Container
    {
        return Container::parse(new AvifDecoder(), $file->build());
    }
}
