<?php

namespace Formwork\Tests\Unit\Images;

use Formwork\Cms\App;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Files\FileFactory;
use Formwork\Images\Exception\UnsupportedConversionException;
use Formwork\Images\Image;
use Formwork\Images\ImageFactory;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Image::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ImageTest extends TestCase
{
    private const string SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><rect width="16" height="16"/></svg>';

    public function testImagesWithoutUriGeneratorCanBeProcessed(): void
    {
        $path = TESTS_TMP_PATH . '/image.png';
        imagepng(imagecreatetruecolor(200, 100), $path);

        // Images made by the image factory, unlike the ones made by the file factory, have no URI generator
        $image = App::instance()->getService(ImageFactory::class)->make($path, ['processPath' => TESTS_TMP_PATH . '/processed']);

        $processed = $image->resize(50, 25)->process();

        $this->assertNotSame($path, $processed->path());
        $this->assertSame(50, $processed->info()->width());
        $this->assertSame(25, $processed->info()->height());

        // The missing URI generator is reported only when an URI is requested
        $this->expectException(FileUriGenerationException::class);
        $processed->uri();
    }

    public function testImagesThatCannotBeTransformedAreSavedWithTheirMetadata(): void
    {
        $image = $this->svgImage();

        // Like when editing the metadata of a file from the Panel
        $image->set('alt', 'A square');
        $image->save();

        $this->assertSame(self::SVG, FileSystem::read($image->path()));
        $this->assertStringContainsString('alt: \'A square\'', FileSystem::read($image->path() . '.meta.yaml'));
    }

    public function testImagesThatCannotBeTransformedCanBeSavedToOtherPaths(): void
    {
        $image = $this->svgImage();

        $image->saveAs(TESTS_TMP_PATH . '/copy.svg');

        $this->assertSame(self::SVG, FileSystem::read(TESTS_TMP_PATH . '/copy.svg'));
    }

    public function testImagesThatCannotBeTransformedCannotBeConverted(): void
    {
        $image = $this->svgImage();

        $this->expectException(UnsupportedConversionException::class);
        $this->expectExceptionMessage('Unsupported image conversion from image/svg+xml to image/png');
        $image->saveAs(TESTS_TMP_PATH . '/converted.png', 'image/png');
    }

    public function testSavingImagesWithoutTransformsWritesOnlyTheirMetadata(): void
    {
        $image = $this->jpegImage();
        $hash = hash_file('sha256', $image->path());

        // Like when editing the metadata of a file from the Panel
        $image->set('alt', 'A photo');
        $image->save();

        $this->assertSame($hash, hash_file('sha256', $image->path()));
        $this->assertStringContainsString('alt: \'A photo\'', FileSystem::read($image->path() . '.meta.yaml'));
    }

    public function testSavingImagesWithTransformsEncodesThemAgain(): void
    {
        $image = $this->jpegImage();
        $path = $image->path();

        $image->resize(50, 25)->save();

        $saved = App::instance()->getService(FileFactory::class)->make($path);
        $this->assertInstanceOf(Image::class, $saved);
        $this->assertSame(50, $saved->info()->width());
        $this->assertSame(25, $saved->info()->height());
    }

    public function testSavingImagesToOtherPathsDoesNotChangeTheirInfo(): void
    {
        $image = $this->jpegImage();

        $image->resize(20, 10)->saveAs(TESTS_TMP_PATH . '/resized.jpg');

        $this->assertSame(200, $image->info()->width());
        $this->assertSame(100, $image->info()->height());

        $resized = App::instance()->getService(FileFactory::class)->make(TESTS_TMP_PATH . '/resized.jpg');
        $this->assertInstanceOf(Image::class, $resized);
        $this->assertSame(20, $resized->info()->width());
    }

    public function testSavingImagesWithTransformsUpdatesTheirInfo(): void
    {
        $image = $this->jpegImage();

        $image->resize(50, 25)->save();

        $this->assertSame(50, $image->info()->width());
        $this->assertSame(25, $image->info()->height());
    }

    private function jpegImage(): Image
    {
        $path = TESTS_TMP_PATH . '/photo.jpg';
        imagejpeg(imagecreatetruecolor(200, 100), $path, 100);

        $image = App::instance()->getService(FileFactory::class)->make($path);
        $this->assertInstanceOf(Image::class, $image);

        return $image;
    }

    private function svgImage(): Image
    {
        $path = TESTS_TMP_PATH . '/image.svg';
        FileSystem::write($path, self::SVG);

        $image = App::instance()->getService(FileFactory::class)->make($path);
        $this->assertInstanceOf(Image::class, $image);

        return $image;
    }
}
