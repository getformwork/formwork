<?php

namespace Formwork\Tests\Unit\Images;

use Formwork\Cms\App;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Files\FileFactory;
use Formwork\Files\FileUriGenerator;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Exception\UnsupportedConversionException;
use Formwork\Images\Exception\UnsupportedTransformsException;
use Formwork\Images\Exception\UnsupportedTypeException;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Image;
use Formwork\Images\ImageFactory;
use Formwork\Images\Transform\Blur;
use Formwork\Images\Transform\BlurMode;
use Formwork\Images\Transform\Brightness;
use Formwork\Images\Transform\Colorize;
use Formwork\Images\Transform\Contrast;
use Formwork\Images\Transform\Crop;
use Formwork\Images\Transform\Desaturate;
use Formwork\Images\Transform\EdgeDetect;
use Formwork\Images\Transform\Emboss;
use Formwork\Images\Transform\Flip;
use Formwork\Images\Transform\FlipDirection;
use Formwork\Images\Transform\Invert;
use Formwork\Images\Transform\Pixelate;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Images\Transform\Rotate;
use Formwork\Images\Transform\Scale;
use Formwork\Images\Transform\Sharpen;
use Formwork\Images\Transform\Smoothen;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Images\Transform\TransformInterface;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Image::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ImageTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/Fixtures';

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

    public function testUnreadableImagesCannotBeRead(): void
    {
        $path = TESTS_TMP_PATH . '/image.jpg';
        imagejpeg(imagecreatetruecolor(20, 10), $path);
        chmod($path, 0o000);

        if (is_readable($path)) {
            $this->markTestSkipped('Files without permissions are still readable by the current user.');
        }

        $image = new Image($path, []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file \"{$path}\" does not exist or is not readable");
        $image->info();
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('transformMethodProvider')]
    public function testTransformMethodsAddTheirTransforms(string $method, array $arguments, TransformInterface $expected): void
    {
        $image = new Image(TESTS_TMP_PATH . '/image.jpg', []);

        $this->assertSame($image, $image->{$method}(...$arguments));
        $this->assertSame($expected->getSpecifier(), $this->transforms($image)->getSpecifier());
    }

    /**
     * @return iterable<string, array{string, list<mixed>, TransformInterface}>
     */
    public static function transformMethodProvider(): iterable
    {
        yield 'rotate' => ['rotate', [90], new Rotate(90)];
        yield 'flip horizontally' => ['flipHorizontal', [], new Flip(FlipDirection::Horizontal)];
        yield 'flip vertically' => ['flipVertical', [], new Flip(FlipDirection::Vertical)];
        yield 'flip both' => ['flipBoth', [], new Flip(FlipDirection::Both)];
        yield 'scale' => ['scale', [0.5], new Scale(0.5)];
        yield 'resize' => ['resize', [50, 25, ResizeMode::Contain], new Resize(50, 25, ResizeMode::Contain)];
        yield 'resize with mode name' => ['resize', [50, 25, 'contain'], new Resize(50, 25, ResizeMode::Contain)];
        yield 'square' => ['square', [50, ResizeMode::Fill], new Resize(50, 50, ResizeMode::Fill)];
        yield 'crop' => ['crop', [1, 2, 3, 4], new Crop(1, 2, 3, 4)];
        yield 'blur' => ['blur', [3, BlurMode::Gaussian], new Blur(3, BlurMode::Gaussian)];
        yield 'brightness' => ['brightness', [20], new Brightness(20)];
        yield 'colorize' => ['colorize', [10, 20, 30], new Colorize(10, 20, 30, 0)];
        yield 'contrast' => ['contrast', [20], new Contrast(20)];
        yield 'desaturate' => ['desaturate', [], new Desaturate()];
        yield 'edge detect' => ['edgedetect', [], new EdgeDetect()];
        yield 'emboss' => ['emboss', [], new Emboss()];
        yield 'invert' => ['invert', [], new Invert()];
        yield 'pixelate' => ['pixelate', [4], new Pixelate(4)];
        yield 'sharpen' => ['sharpen', [], new Sharpen()];
        yield 'smoothen' => ['smoothen', [], new Smoothen()];
    }

    public function testSquaresDefaultToTheShortestSide(): void
    {
        $image = $this->jpegImage()->square();

        $this->assertSame((new Resize(100, 100))->getSpecifier(), $this->transforms($image)->getSpecifier());
    }

    public function testImagesWithoutTransformsAreNotProcessed(): void
    {
        $image = $this->processableImage();

        $this->assertSame($image, $image->process());
    }

    public function testProcessedImagesAreCached(): void
    {
        $image = $this->processableImage();

        $first = $image->resize(50, 25)->process();
        $second = $image->resize(50, 25)->process();
        $other = $image->resize(40, 20)->process();

        $this->assertSame($first->path(), $second->path());
        $this->assertNotSame($first->path(), $other->path());
    }

    public function testProcessedImagesKeepTheUriGenerator(): void
    {
        $processed = $this->jpegImage()->resize(50, 25)->process();

        $this->assertStringContainsString('/photo.jpg', $processed->uri());
    }

    public function testImagesThatCannotBeTransformedCanBeCached(): void
    {
        $processed = $this->svgImage()->process(forceCache: true);

        $this->assertSame(self::SVG, FileSystem::read($processed->path()));
        $this->assertNotSame(TESTS_TMP_PATH . '/image.svg', $processed->path());
    }

    public function testProcessedImagesKeepTheirData(): void
    {
        $image = $this->processableImage();
        $image->set('alt', 'A photo');

        $this->assertSame('A photo', $image->resize(50, 25)->process()->get('alt'));
    }

    public function testDimensionsIncludeTheTransforms(): void
    {
        $image = $this->processableImage();

        $this->assertSame(50, $image->resize(50, 25)->width());
        $this->assertSame(25, $image->resize(50, 25)->height());
        $this->assertSame(200, $image->info()->width());
    }

    #[DataProvider('conversionProvider')]
    public function testImagesAreConvertedToOtherTypes(string $method, string $mimeType): void
    {
        if ($mimeType === 'image/avif' && !(gd_info()['AVIF Support'] ?? false)) {
            $this->markTestSkipped('The GD extension is built without AVIF support.');
        }

        $converted = $this->processableImage()->{$method}();

        $this->assertSame($mimeType, $converted->mimeType());
        $this->assertSame($mimeType, mime_content_type($converted->path()));
        $this->assertSame(200, $converted->info()->width());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function conversionProvider(): iterable
    {
        yield 'GIF' => ['toGif', 'image/gif'];
        yield 'JPEG' => ['toJpeg', 'image/jpeg'];
        yield 'PNG' => ['toPng', 'image/png'];
        yield 'WebP' => ['toWebp', 'image/webp'];
        yield 'AVIF' => ['toAvif', 'image/avif'];
    }

    public function testImagesCannotBeConvertedToUnsupportedTypes(): void
    {
        $image = $this->processableImage();

        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage('Unsupported image type image/bmp');
        $image->process('image/bmp');
    }

    public function testImagesCannotBeSavedAsUnsupportedTypes(): void
    {
        $image = $this->jpegImage();

        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage('Unsupported image type image/bmp');
        $image->saveAs(TESTS_TMP_PATH . '/image.bmp', 'image/bmp');
    }

    public function testImagesOfUnsupportedTypesCannotBeRead(): void
    {
        $path = TESTS_TMP_PATH . '/image.bmp';
        imagebmp(imagecreatetruecolor(20, 10), $path);
        $image = App::instance()->getService(ImageFactory::class)->make($path);

        $this->assertSame('image/bmp', $image->mimeType());

        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage('Unsupported image type');
        $image->info();
    }

    public function testFilesThatAreNotImagesCannotBeRead(): void
    {
        $path = TESTS_TMP_PATH . '/image.jpg';
        FileSystem::write($path, 'Not an image');
        $image = App::instance()->getService(ImageFactory::class)->make($path);

        $this->assertSame('text/plain', $image->mimeType());

        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage('Unsupported image type');
        $image->info();
    }

    public function testImagesThatCannotBeTransformedCannotBeSavedWithTransforms(): void
    {
        $image = $this->svgImage()->resize(8, 8);

        $this->expectException(UnsupportedTransformsException::class);
        $this->expectExceptionMessage('Image type image/svg+xml does not support transformations');
        $image->saveAs(TESTS_TMP_PATH . '/resized.svg');
    }

    public function testColorProfilesCanBeSetAndRemoved(): void
    {
        $image = $this->jpegImage();
        $this->assertFalse($image->hasColorProfile());

        $image->setColorProfile(new ColorProfile((string) file_get_contents(self::FIXTURES . '/srgb.icc')));
        $this->assertTrue($image->hasColorProfile());
        $this->assertSame((string) file_get_contents(self::FIXTURES . '/srgb.icc'), $image->getColorProfile()?->getData());

        $image->removeColorProfile();
        $this->assertFalse($image->hasColorProfile());
    }

    public function testExifDataCanBeSetAndRemoved(): void
    {
        $image = $this->jpegImage();
        $this->assertFalse($image->hasExifData());

        $image->setExifData(new ExifData((string) file_get_contents(self::FIXTURES . '/exif.tiff')));
        $this->assertTrue($image->hasExifData());
        $this->assertSame('FormworkTest', $image->getExifData()?->get('Make'));

        $image->removeExifData();
        $this->assertFalse($image->hasExifData());
    }

    public function testArraysIncludeTheImageInfo(): void
    {
        $image = $this->processableImage();
        $fileUriGenerator = $this->createStub(FileUriGenerator::class);
        $fileUriGenerator->method('generate')->willReturn('/files/photo.jpg');
        $image->setUriGenerator($fileUriGenerator);

        $data = $image->toArray();

        $this->assertSame($image->info()->toArray(), $data['imageInfo']);
        $this->assertSame('/files/photo.jpg', $data['uri']);

        $keys = array_keys($data);
        sort($keys);
        $this->assertSame($keys, array_keys($data));
    }

    private function processableImage(): Image
    {
        $path = TESTS_TMP_PATH . '/photo.jpg';
        imagejpeg(imagecreatetruecolor(200, 100), $path, 100);

        return App::instance()->getService(ImageFactory::class)->make($path, ['processPath' => TESTS_TMP_PATH . '/processed']);
    }

    private function transforms(Image $image): TransformCollection
    {
        $transforms = $image->get('transforms');
        $this->assertInstanceOf(TransformCollection::class, $transforms);

        return $transforms;
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
