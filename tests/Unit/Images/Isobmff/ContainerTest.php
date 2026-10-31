<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Isobmff\Container;
use Formwork\Images\Isobmff\DataArea;
use Formwork\Images\Isobmff\ItemInfoBox;
use Formwork\Images\Isobmff\ItemInfoEntry;
use Formwork\Images\Isobmff\ItemLocationBox;
use Formwork\Images\Isobmff\ItemReferenceBox;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Container::class)]
#[CoversClass(DataArea::class)]
final class ContainerTest extends TestCase
{
    public function testTheMajorBrandIsRead(): void
    {
        $this->assertSame('avif', $this->parse(AvifFile::create())->majorBrand());
        $this->assertSame('avis', $this->parse(AvifFile::create()->asSequence())->majorBrand());
    }

    public function testTheMetadataIsFound(): void
    {
        $container = $this->parse(AvifFile::create());

        $this->assertNotNull($container->meta());
        $this->assertSame($container->meta(), $container->requireMeta());
    }

    public function testTheMissingMetadataIsReported(): void
    {
        $container = Container::parse(new AvifDecoder(), AvifFile::box('ftyp', 'avif' . pack('N', 0) . 'avif'));

        $this->assertNull($container->meta());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not contain a meta box');

        $container->requireMeta();
    }

    public function testItemsAreFoundByType(): void
    {
        $container = $this->parse(AvifFile::create()->withAlpha()->withExif('TIFF'));

        $this->assertSame([AvifFile::PRIMARY_ID, AvifFile::ALPHA_ID], $container->itemIdsByType('av01'));
        $this->assertSame([AvifFile::EXIF_ID], $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertSame([], $container->itemIdsByType('mime'));
    }

    public function testItemsHaveNoTypeWithoutTheItemInformation(): void
    {
        $container = Container::parse(new AvifDecoder(), AvifFile::box('ftyp', 'avif' . pack('N', 0) . 'avif'));

        $this->assertSame([], $container->itemIdsByType('av01'));
    }

    public function testTheDataOfItemsStoredInTheFileIsRead(): void
    {
        $container = $this->parse(AvifFile::create()->withAlpha());

        $this->assertSame(AvifFile::PRIMARY_DATA, $container->itemData(AvifFile::PRIMARY_ID));
        $this->assertSame(AvifFile::ALPHA_DATA, $container->itemData(AvifFile::ALPHA_ID));
    }

    public function testTheDataOfItemsStoredInTheItemDataBoxIsRead(): void
    {
        $container = $this->parse(AvifFile::create()->withRawExif('in the item data box')->withLocationVersion(1)->withItemDataBox());

        $this->assertSame('in the item data box', $container->itemData(AvifFile::EXIF_ID));
    }

    public function testTheDataOfUnknownItemsIsNotRead(): void
    {
        $this->assertNull($this->parse(AvifFile::create())->itemData(99));
    }

    public function testTheDataOfItemsStoredInOtherFilesIsNotRead(): void
    {
        $container = $this->parse(AvifFile::create()->withRawExif('elsewhere')->withDataReference(AvifFile::EXIF_ID));

        $this->assertNull($container->itemData(AvifFile::EXIF_ID));
    }

    public function testExtentsWithoutALengthAreNotSupported(): void
    {
        $container = $this->parse(AvifFile::create()->withRawExif('data')->withExtentOverride(AvifFile::EXIF_ID, length: 0));

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('Item extents without an explicit length are not supported');

        $container->itemData(AvifFile::EXIF_ID);
    }

    public function testExtentsBeyondTheAvailableDataAreRejected(): void
    {
        $container = $this->parse(AvifFile::create()->withRawExif('data')->withExtentOverride(AvifFile::EXIF_ID, offset: 100000));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Item extent exceeds the available data');

        $container->itemData(AvifFile::EXIF_ID);
    }

    public function testItemsAreAdded(): void
    {
        $container = $this->parse(AvifFile::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');

        $this->assertSame(2, $id);
        $this->assertSame('added', $container->itemData($id));
        $this->assertSame([$id], $container->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
    }

    public function testAddedItemsAreWrittenToTheFile(): void
    {
        $container = $this->parse(AvifFile::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame('added', $rewritten->itemData($id));
        $this->assertSame(AvifFile::PRIMARY_DATA, $rewritten->itemData(AvifFile::PRIMARY_ID));
    }

    public function testItemsWithoutDataHaveNoExtents(): void
    {
        $container = $this->parse(AvifFile::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, '');

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame('', $rewritten->itemData($id));
        $this->assertSame([], $rewritten->requireMeta()->require(ItemLocationBox::class)->find($id)?->extents);
    }

    public function testReferencesAreAddedToFilesWithoutThem(): void
    {
        $container = $this->parse(AvifFile::create());
        $container->addReference(ItemReferenceBox::CONTENT_DESCRIBES, 2, AvifFile::PRIMARY_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertNotNull($rewritten->requireMeta()->find(ItemReferenceBox::class));
        $this->assertStringContainsString(ItemReferenceBox::CONTENT_DESCRIBES, $container->toString());
    }

    public function testReferencesAreAddedToTheExistingBox(): void
    {
        $container = $this->parse(AvifFile::create()->withAlpha());
        $container->addReference(ItemReferenceBox::CONTENT_DESCRIBES, 4, AvifFile::PRIMARY_ID);

        $this->assertSame(1, substr_count($container->toString(), ItemReferenceBox::TYPE));
        $this->assertStringContainsString('auxl', $container->toString());
    }

    public function testItemsAreRemovedWithTheirDataAndReferences(): void
    {
        $container = $this->parse(AvifFile::create()->withAlpha()->withRawExif('metadata'));
        $container->removeItem(AvifFile::EXIF_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertSame([], $rewritten->itemIdsByType(ItemInfoEntry::EXIF_TYPE));
        $this->assertNull($rewritten->itemData(AvifFile::EXIF_ID));
        $this->assertStringNotContainsString('metadata', $rewritten->toString());
        $this->assertStringNotContainsString(ItemReferenceBox::CONTENT_DESCRIBES, $rewritten->toString());
    }

    public function testRemovedItemsDoNotKeepTheirAddedData(): void
    {
        $container = $this->parse(AvifFile::create());
        $id = $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added');
        $container->removeItem($id);

        $this->assertStringNotContainsString('added', $container->toString());
    }

    #[DataProvider('layoutProvider')]
    public function testTheDataOfTheItemsSurvivesTheRewriting(AvifFile $file): void
    {
        $rewritten = Container::parse(new AvifDecoder(), $this->parse($file)->toString());

        $this->assertSame(AvifFile::PRIMARY_DATA, $rewritten->itemData(AvifFile::PRIMARY_ID));
        $this->assertSame(AvifFile::ALPHA_DATA, $rewritten->itemData(AvifFile::ALPHA_ID));
        $this->assertSame('metadata', $rewritten->itemData(AvifFile::EXIF_ID));
    }

    /**
     * @return iterable<string, array{AvifFile}>
     */
    public static function layoutProvider(): iterable
    {
        $file = static fn(): AvifFile => AvifFile::create()->withAlpha()->withRawExif('metadata');

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
        $rewritten = $this->parse(AvifFile::create()->withFreeSpace())->toString();

        $this->assertStringContainsString('free-space', $rewritten);
        $this->assertSame('avif', Container::parse(new AvifDecoder(), $rewritten)->majorBrand());
        $this->assertSame(AvifFile::PRIMARY_DATA, Container::parse(new AvifDecoder(), $rewritten)->itemData(AvifFile::PRIMARY_ID));
    }

    public function testTheRewritingIsIdempotent(): void
    {
        $once = $this->parse(AvifFile::create()->withAlpha()->withRawExif('metadata'))->toString();
        $twice = Container::parse(new AvifDecoder(), $once)->toString();

        $this->assertSame($once, $twice);
    }

    public function testImageSequencesExposeTheirTimeline(): void
    {
        $sequence = $this->parse(AvifFile::create()->withFrames(6, 2))->imageSequence();

        $this->assertNotNull($sequence);
        $this->assertSame(6, $sequence->frameCount);
        $this->assertSame(2, $sequence->playCount);
    }

    public function testStillImagesHaveNoTimeline(): void
    {
        $this->assertNull($this->parse(AvifFile::create())->imageSequence());
    }

    public function testImageSequencesCannotBeRewritten(): void
    {
        $container = $this->parse(AvifFile::create()->asSequence());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('Modifying AVIF image sequences is not supported');

        $container->toString();
    }

    public function testItemsPointingToOtherFilesAreLeftUntouched(): void
    {
        $container = $this->parse(AvifFile::create()->withRawExif('elsewhere')->withDataReference(AvifFile::EXIF_ID));
        $before = $container->requireMeta()->require(ItemLocationBox::class)->find(AvifFile::EXIF_ID);

        $rewritten = Container::parse(new AvifDecoder(), $container->toString());

        $this->assertEquals($before, $rewritten->requireMeta()->require(ItemLocationBox::class)->find(AvifFile::EXIF_ID));
    }

    public function testTheItemInformationIsRequiredToAddItems(): void
    {
        $container = $this->parse(AvifFile::create());
        $container->requireMeta()->require(ItemInfoBox::class)->remove(AvifFile::PRIMARY_ID);

        $this->assertSame(1, $container->addItem(ItemInfoEntry::EXIF_TYPE, 'added'));
    }

    private function parse(AvifFile $file): Container
    {
        return Container::parse(new AvifDecoder(), $file->build());
    }
}
