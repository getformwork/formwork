<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\AvifHandler;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AvifHandler::class)]
final class AvifHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = $this->encoded()->getInfo();

        $this->assertSame('image/avif', $info->mimeType());
        $this->assertSame(64, $info->width());
        $this->assertSame(48, $info->height());
        $this->assertSame(ColorSpace::RGB, $info->colorSpace());
        $this->assertSame(8, $info->colorDepth());
        $this->assertTrue($info->hasAlphaChannel());
        $this->assertFalse($info->isAnimation());
    }

    public function testInfoIsReadFromThePropertiesOfThePrimaryItem(): void
    {
        $info = $this->handler(AvifFile::create()->withDimensions(1920, 1080)->withDepth(10))->getInfo();

        $this->assertSame(1920, $info->width());
        $this->assertSame(1080, $info->height());
        $this->assertSame(10, $info->colorDepth());
        $this->assertFalse($info->hasAlphaChannel());
    }

    public function testInfoFallsBackToTheDefaultsWithoutProperties(): void
    {
        $info = $this->handler(AvifFile::create()->withoutItemProperties())->getInfo();

        $this->assertSame(0, $info->width());
        $this->assertSame(0, $info->height());
        $this->assertSame(8, $info->colorDepth());
        $this->assertNull($info->colorNumber());
        $this->assertNull($info->animationFrames());
        $this->assertNull($info->animationRepeatCount());
    }

    public function testInfoReadsThePropertiesOfAnyItemWithoutAPrimaryOne(): void
    {
        $info = $this->handler(AvifFile::create()->withDimensions(32, 24)->withoutPrimaryItem())->getInfo();

        $this->assertSame(32, $info->width());
        $this->assertSame(24, $info->height());
    }

    public function testInfoKeepsTheDefaultSizeWithoutSpatialExtents(): void
    {
        $info = $this->handler(AvifFile::create()->withoutDimensions()->withDepth(10))->getInfo();

        $this->assertSame(0, $info->width());
        $this->assertSame(0, $info->height());
        $this->assertSame(10, $info->colorDepth());
    }

    public function testDepthIsTheDefaultOneWhenTheChannelsAreNotDeclared(): void
    {
        $this->assertSame(8, $this->handler(AvifFile::create()->withDepth(0))->getInfo()->colorDepth());
    }

    public function testAlphaChannelIsDetectedFromTheAuxiliaryType(): void
    {
        $this->assertTrue($this->handler(AvifFile::create()->withAlpha())->getInfo()->hasAlphaChannel());
    }

    public function testImageSequencesAreAnimations(): void
    {
        $this->assertTrue($this->handler(AvifFile::create()->asSequence())->getInfo()->isAnimation());
    }

    public function testImageSequencesHaveAFrameCountAndARepeatCount(): void
    {
        $info = $this->handler(AvifFile::create()->withFrames(12, 3))->getInfo();

        $this->assertSame(12, $info->animationFrames());
        $this->assertSame(3, $info->animationRepeatCount());
    }

    public function testImageSequencesCanLoopForever(): void
    {
        $this->assertSame(0, $this->handler(AvifFile::create()->withFrames(5, 0))->getInfo()->animationRepeatCount());
    }

    public function testStillImagesHaveNoFrameCount(): void
    {
        $info = $this->encoded()->getInfo();

        $this->assertNull($info->animationFrames());
        $this->assertNull($info->animationRepeatCount());
    }

    public function testMonochromeImagesAreGrayscale(): void
    {
        $this->assertSame(ColorSpace::Grayscale, $this->handler(AvifFile::create()->withCodecConfiguration(monochrome: true))->getInfo()->colorSpace());
        $this->assertSame(ColorSpace::RGB, $this->handler(AvifFile::create()->withCodecConfiguration(monochrome: false))->getInfo()->colorSpace());
        $this->assertSame(ColorSpace::RGB, $this->handler(AvifFile::create())->getInfo()->colorSpace());
    }

    public function testTransformsAreSupportedOnlyForStillImages(): void
    {
        $this->assertTrue($this->encoded()->supportsTransforms());
        $this->assertFalse($this->handler(AvifFile::create()->asSequence())->supportsTransforms());
    }

    public function testColorProfilesAndExifDataAreSupported(): void
    {
        $this->assertTrue(AvifHandler::supportsColorProfile());
        $this->assertTrue(AvifHandler::supportsExifData());
    }

    public function testColorProfileIsReadFromAnEncodedImage(): void
    {
        $handler = $this->encoded();

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfileData(), $handler->getColorProfile()?->getData());
    }

    #[DataProvider('colorInformationTypeProvider')]
    public function testColorProfileIsReadFromBothColorInformationTypes(string $type): void
    {
        $handler = $this->handler(AvifFile::create()->withColorProfile($this->colorProfileData(), $type));

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfileData(), $handler->getColorProfile()?->getData());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function colorInformationTypeProvider(): iterable
    {
        yield 'unrestricted profile' => ['prof'];
        yield 'restricted profile' => ['rICC'];
    }

    public function testColorProfileIsNotReadFromOtherColorInformation(): void
    {
        $handler = $this->handler(AvifFile::create()->withColorProfile(str_repeat("\x00", 15), 'nclx'));

        $this->assertFalse($handler->hasColorProfile());
        $this->assertNull($handler->getColorProfile());
    }

    public function testColorProfileIsMissingWithoutProperties(): void
    {
        $this->assertFalse($this->handler(AvifFile::create()->withoutItemProperties())->hasColorProfile());
    }

    public function testColorProfileIsAdded(): void
    {
        $handler = $this->handler(AvifFile::create());
        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));

        $this->assertSame($this->colorProfileData(), $this->reread($handler)->getColorProfile()?->getData());
    }

    public function testColorProfileIsAddedToAnEncodedImage(): void
    {
        $handler = $this->encoded();
        $handler->setColorProfile(new ColorProfile($this->colorProfileData('lab')));

        $reread = $this->reread($handler);

        $this->assertSame($this->colorProfileData('lab'), $reread->getColorProfile()?->getData());
        $this->assertSame(64, $reread->getInfo()->width());
        $this->assertTrue($reread->getInfo()->hasAlphaChannel());
    }

    public function testColorProfileReplacesTheExistingOne(): void
    {
        $handler = $this->handler(AvifFile::create()->withColorProfile($this->colorProfileData()));
        $handler->setColorProfile(new ColorProfile($this->colorProfileData('lab')));

        $this->assertSame($this->colorProfileData('lab'), $this->reread($handler)->getColorProfile()?->getData());
        $this->assertSame(1, substr_count($handler->getData(), 'colr'));
    }

    public function testColorProfileKeepsTheAssociationsOfTheOtherProperties(): void
    {
        $handler = $this->handler(AvifFile::create()->withAlpha()->withColorProfile($this->colorProfileData()));
        $handler->setColorProfile(new ColorProfile($this->colorProfileData('lab')));

        $info = $this->reread($handler)->getInfo();

        $this->assertSame(64, $info->width());
        $this->assertSame(48, $info->height());
        $this->assertTrue($info->hasAlphaChannel());
    }

    public function testColorProfileIsRemoved(): void
    {
        $handler = $this->handler(AvifFile::create()->withColorProfile($this->colorProfileData()));
        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertFalse($this->reread($handler)->hasColorProfile());
        $this->assertStringNotContainsString('colr', $handler->getData());
    }

    public function testColorProfileIsRemovedFromAnEncodedImage(): void
    {
        $handler = $this->encoded();
        $handler->removeColorProfile();

        $reread = $this->reread($handler);

        $this->assertFalse($reread->hasColorProfile());
        $this->assertSame('FormworkTest Model-X', $reread->getExifData()?->makeAndModel());
        $this->assertTrue($reread->getInfo()->hasAlphaChannel());
    }

    public function testRemovingAMissingColorProfileLeavesTheImageUntouched(): void
    {
        $data = AvifFile::create()->build();
        $handler = new AvifHandler($data);
        $handler->removeColorProfile();

        $this->assertSame($data, $handler->getData());
    }

    public function testExifDataIsReadFromAnEncodedImage(): void
    {
        $handler = $this->encoded();

        $this->assertTrue($handler->hasExifData());
        $this->assertStringStartsWith('MM', $handler->getExifData()?->getData() ?? '');
        $this->assertSame('FormworkTest Model-X', $handler->getExifData()?->makeAndModel());
    }

    public function testExifDataIsReadAfterTheHeaderOffset(): void
    {
        $handler = $this->handler(AvifFile::create()->withExif($this->exifData(), 6));

        $this->assertSame($this->exifData(), $handler->getExifData()?->getData());
    }

    #[DataProvider('incompleteExifItemProvider')]
    public function testExifDataIsMissingWhenTheItemIsTooShortToHoldIt(string $data): void
    {
        $handler = $this->handler(AvifFile::create()->withRawExif($data));

        $this->assertTrue($handler->hasExifData());
        $this->assertNull($handler->getExifData());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function incompleteExifItemProvider(): iterable
    {
        yield 'shorter than the header offset' => ["\x00\x00"];
        yield 'without data after the header offset' => ["\x00\x00\x00\x00"];
    }

    public function testExifDataIsMissingWhenTheItemIsNotDeclared(): void
    {
        $handler = $this->handler(AvifFile::create());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());
    }

    #[DataProvider('itemLayoutProvider')]
    public function testExifDataIsReadFromAnyItemLayout(AvifFile $file): void
    {
        $this->assertSame($this->exifData(), $this->handler($file)->getExifData()?->getData());
    }

    /**
     * @return iterable<string, array{AvifFile}>
     */
    public static function itemLayoutProvider(): iterable
    {
        $exif = (string) file_get_contents(self::FIXTURES . '/exif.tiff');

        yield 'item location version 0' => [AvifFile::create()->withExif($exif)];
        yield 'item location version 1' => [AvifFile::create()->withExif($exif)->withLocationVersion(1)];
        yield 'item location version 2' => [AvifFile::create()->withExif($exif)->withLocationVersion(2)];
        yield 'base offsets' => [AvifFile::create()->withExif($exif)->withLocationVersion(1, 4)];
        yield 'extent indexes' => [AvifFile::create()->withExif($exif)->withLocationVersion(2)->withExtentIndexes()];
        yield 'item data box' => [AvifFile::create()->withExif($exif)->withLocationVersion(1)->withItemDataBox()];
        yield 'media data before the metadata' => [AvifFile::create()->withExif($exif)->withMediaDataFirst()];
        yield 'media data with a 64-bit size' => [AvifFile::create()->withExif($exif)->withLargeMediaDataBox()];
        yield 'media data without a size' => [AvifFile::create()->withExif($exif)->withUnsizedMediaDataBox()];
    }

    #[DataProvider('itemLayoutProvider')]
    public function testExifDataSurvivesTheRewritingOfAnyItemLayout(AvifFile $file): void
    {
        $handler = $this->handler($file);
        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));

        $this->assertSame($this->exifData(), $this->reread($handler)->getExifData()?->getData());
    }

    public function testExifDataIsAdded(): void
    {
        $handler = $this->handler(AvifFile::create());
        $handler->setExifData(new ExifData($this->exifData()));

        $reread = $this->reread($handler);

        $this->assertTrue($reread->hasExifData());
        $this->assertSame($this->exifData(), $reread->getExifData()?->getData());
    }

    public function testExifDataIsAddedToAnImageWithoutReferences(): void
    {
        $handler = $this->handler(AvifFile::create());
        $handler->setExifData(new ExifData($this->exifData()));

        $this->assertStringContainsString('iref', $handler->getData());
        $this->assertStringContainsString('cdsc', $handler->getData());
    }

    public function testExifDataIsAddedToAnImageWithExistingReferences(): void
    {
        $handler = $this->handler(AvifFile::create()->withAlpha());
        $handler->setExifData(new ExifData($this->exifData()));

        $reread = $this->reread($handler);

        $this->assertSame($this->exifData(), $reread->getExifData()?->getData());
        $this->assertTrue($reread->getInfo()->hasAlphaChannel());
        $this->assertSame(1, substr_count($handler->getData(), 'iref'));
    }

    public function testExifDataReplacesTheExistingOne(): void
    {
        $handler = $this->handler(AvifFile::create()->withExif($this->exifData()));
        $handler->setExifData(new ExifData($this->exifData('alternate')));

        $this->assertSame($this->exifData('alternate'), $this->reread($handler)->getExifData()?->getData());
        $this->assertSame(1, substr_count($handler->getData(), 'Exif'));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = $this->handler(AvifFile::create()->withExif($this->exifData()));
        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertFalse($this->reread($handler)->hasExifData());
        $this->assertStringNotContainsString('cdsc', $handler->getData());
    }

    public function testExifDataIsRemovedFromAnEncodedImage(): void
    {
        $handler = $this->encoded();
        $handler->removeExifData();

        $reread = $this->reread($handler);

        $this->assertFalse($reread->hasExifData());
        $this->assertSame($this->colorProfileData(), $reread->getColorProfile()?->getData());
        $this->assertTrue($reread->getInfo()->hasAlphaChannel());
    }

    public function testRemovingMissingExifDataLeavesTheImageUntouched(): void
    {
        $data = AvifFile::create()->build();
        $handler = new AvifHandler($data);
        $handler->removeExifData();

        $this->assertSame($data, $handler->getData());
    }

    public function testMetadataIsWrittenInASingleRewrite(): void
    {
        $handler = $this->encoded();
        $handler->setColorProfile(new ColorProfile($this->colorProfileData('lab')));
        $handler->setExifData(new ExifData($this->exifData('alternate')));

        $reread = $this->reread($handler);

        $this->assertSame($this->colorProfileData('lab'), $reread->getColorProfile()?->getData());
        $this->assertSame($this->exifData('alternate'), $reread->getExifData()?->getData());
        $this->assertSame(64, $reread->getInfo()->width());
        $this->assertTrue($reread->getInfo()->hasAlphaChannel());
    }

    public function testRewritingIsIdempotent(): void
    {
        $handler = $this->handler(AvifFile::create()->withAlpha()->withExif($this->exifData()));
        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));
        $once = $handler->getData();

        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));

        $this->assertSame($once, $handler->getData());
    }

    public function testImageDataIsPreservedByTheRewriting(): void
    {
        $handler = $this->handler(AvifFile::create()->withAlpha());
        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));

        $this->assertStringContainsString(AvifFile::PRIMARY_DATA, $handler->getData());
        $this->assertStringContainsString(AvifFile::ALPHA_DATA, $handler->getData());
    }

    #[DataProvider('modificationProvider')]
    public function testImageSequencesCannotBeModified(string $method): void
    {
        $handler = $this->handler(AvifFile::create()->asSequence()->withColorProfile($this->colorProfileData())->withExif($this->exifData()));

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('Modifying AVIF image sequences is not supported');

        $this->modify($handler, $method);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function modificationProvider(): iterable
    {
        yield 'set color profile' => ['setColorProfile'];
        yield 'remove color profile' => ['removeColorProfile'];
        yield 'set Exif data' => ['setExifData'];
        yield 'remove Exif data' => ['removeExifData'];
    }

    public function testImagesWithoutMetadataCannotBeModified(): void
    {
        $handler = new AvifHandler(AvifFile::box('ftyp', 'avif' . pack('N', 0) . 'avif'));

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not contain a meta box');

        $handler->setExifData(new ExifData($this->exifData()));
    }

    public function testColorProfilesNeedAPrimaryItem(): void
    {
        $handler = $this->handler(AvifFile::create()->withoutPrimaryItem());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not define a primary item');

        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));
    }

    public function testColorProfilesNeedTheItemProperties(): void
    {
        $handler = $this->handler(AvifFile::create()->withoutItemProperties());

        $this->expectException(UnsupportedFeatureException::class);
        $this->expectExceptionMessage('AVIF file does not contain a required "iprp" box');

        $handler->setColorProfile(new ColorProfile($this->colorProfileData()));
    }

    public function testDataIsSetFromAGdImage(): void
    {
        if (!(gd_info()['AVIF Support'] ?? false)) {
            $this->markTestSkipped('The GD extension is built without AVIF support.');
        }

        $image = imagecreatetruecolor(8, 6);
        $this->assertNotFalse($image);

        $handler = AvifHandler::fromGdImage($image);

        $this->assertNotSame('', $handler->getData());
        $this->assertSame(8, $handler->getInfo()->width());
        $this->assertSame(6, $handler->getInfo()->height());
    }

    public function testDataIsSetFromAPaletteGdImage(): void
    {
        if (!(gd_info()['AVIF Support'] ?? false)) {
            $this->markTestSkipped('The GD extension is built without AVIF support.');
        }

        $image = imagecreate(8, 6);
        $this->assertNotFalse($image);
        imagecolorallocate($image, 255, 0, 0);

        $this->assertSame(8, AvifHandler::fromGdImage($image)->getInfo()->width());
    }

    private function encoded(): AvifHandler
    {
        return AvifHandler::fromPath(self::FIXTURES . '/image.avif');
    }

    private function handler(AvifFile $file): AvifHandler
    {
        return new AvifHandler($file->build());
    }

    /**
     * Parse again the data written by a handler, to read it back the way a decoder would
     */
    private function reread(AvifHandler $handler): AvifHandler
    {
        return new AvifHandler($handler->getData());
    }

    private function colorProfileData(string $name = 'srgb'): string
    {
        return (string) file_get_contents(self::FIXTURES . '/' . $name . '.icc');
    }

    private function exifData(string $name = 'exif'): string
    {
        return (string) file_get_contents(self::FIXTURES . '/' . $name . '.tiff');
    }

    private function modify(AvifHandler $handler, string $method): void
    {
        match ($method) {
            'setColorProfile'    => $handler->setColorProfile(new ColorProfile($this->colorProfileData())),
            'removeColorProfile' => $handler->removeColorProfile(),
            'setExifData'        => $handler->setExifData(new ExifData($this->exifData())),
            default              => $handler->removeExifData(),
        };
    }
}
