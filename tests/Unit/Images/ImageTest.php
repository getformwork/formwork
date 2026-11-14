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

    private function svgImage(): Image
    {
        $path = TESTS_TMP_PATH . '/image.svg';
        FileSystem::write($path, self::SVG);

        $image = App::instance()->getService(FileFactory::class)->make($path);
        $this->assertInstanceOf(Image::class, $image);

        return $image;
    }
}
