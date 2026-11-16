<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Resize::class)]
final class ResizeTest extends TestCase
{
    public function testMissingDimensionsAreComputedForEachImage(): void
    {
        $resize = new Resize(width: 50);
        $specifier = $resize->getSpecifier();

        $portrait = $resize->apply(imagecreatetruecolor(100, 200), new ImageInfo(['hasAlphaChannel' => false]));
        $landscape = $resize->apply(imagecreatetruecolor(200, 100), new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([50, 100], [imagesx($portrait), imagesy($portrait)]);
        $this->assertSame([50, 25], [imagesx($landscape), imagesy($landscape)]);

        // The transform is not changed, so it identifies the same processed images in the cache
        $this->assertSame($specifier, $resize->getSpecifier());
    }

    /**
     * @param array{int<1, max>, int<1, max>} $source
     * @param array{int, int}                 $expected
     */
    #[DataProvider('resizeModeProvider')]
    public function testResizeModesDetermineTheDimensions(ResizeMode $resizeMode, array $source, array $expected): void
    {
        $gdImage = (new Resize(50, 50, $resizeMode))->apply(imagecreatetruecolor(...$source), new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame($expected, [imagesx($gdImage), imagesy($gdImage)]);
    }

    /**
     * @return iterable<string, array{ResizeMode, array{int<1, max>, int<1, max>}, array{int, int}}>
     */
    public static function resizeModeProvider(): iterable
    {
        yield 'fill' => [ResizeMode::Fill, [200, 100], [50, 50]];
        yield 'cover of a landscape image' => [ResizeMode::Cover, [200, 100], [50, 50]];
        yield 'cover of a portrait image' => [ResizeMode::Cover, [100, 200], [50, 50]];
        yield 'contain of a landscape image' => [ResizeMode::Contain, [200, 100], [50, 25]];
        yield 'contain of a portrait image' => [ResizeMode::Contain, [100, 200], [25, 50]];
        yield 'center of a landscape image' => [ResizeMode::Center, [200, 100], [50, 50]];
        yield 'center of a portrait image' => [ResizeMode::Center, [100, 200], [50, 50]];
    }

    /**
     * @param array{int, int} $pixel
     */
    #[DataProvider('edgeProvider')]
    public function testOnlyFillModeKeepsTheEdgesOfTheImage(ResizeMode $resizeMode, array $pixel, int $expected): void
    {
        // The left quarter of the image is red, and is cropped away when covering a square
        $gdImage = imagecreatetruecolor(200, 100);
        imagefilledrectangle($gdImage, 0, 0, 49, 99, 0xFF0000);

        $gdImage = (new Resize(50, 50, $resizeMode))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame($expected, imagecolorat($gdImage, ...$pixel));
    }

    /**
     * @return iterable<string, array{ResizeMode, array{int, int}, int}>
     */
    public static function edgeProvider(): iterable
    {
        yield 'fill' => [ResizeMode::Fill, [1, 25], 0xFF0000];
        yield 'cover' => [ResizeMode::Cover, [1, 25], 0x000000];
    }

    public function testCenteredImagesAreSurroundedByTransparency(): void
    {
        $gdImage = imagecreatetruecolor(200, 100);
        imagefill($gdImage, 0, 0, 0xFF0000);

        $gdImage = (new Resize(50, 50, ResizeMode::Center))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => true]));

        // The image is 25 pixels high, so it is moved down by 12 pixels
        $this->assertSame(127, imagecolorat($gdImage, 25, 5) >> 24);
        $this->assertSame(0xFF0000, imagecolorat($gdImage, 25, 25));
    }

    #[DataProvider('invalidDimensionsProvider')]
    public function testDimensionsMustBeValid(?int $width, ?int $height, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new Resize($width, $height);
    }

    /**
     * @return iterable<string, array{?int, ?int, string}>
     */
    public static function invalidDimensionsProvider(): iterable
    {
        yield 'no dimensions' => [null, null, 'At least one of $width or $height must be specified'];
        yield 'zero width' => [0, 10, '$width must be greater than 0'];
        yield 'zero height' => [10, 0, '$height must be greater than 0'];
    }

    /**
     * @param array{int<1, max>, int<1, max>} $source
     */
    #[DataProvider('vanishingDimensionProvider')]
    public function testImagesCannotBeResizedToNothing(array $source, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);
        (new Resize(10, 10, ResizeMode::Contain))->apply(imagecreatetruecolor(...$source), new ImageInfo(['hasAlphaChannel' => false]));
    }

    /**
     * @return iterable<string, array{array{int<1, max>, int<1, max>}, string}>
     */
    public static function vanishingDimensionProvider(): iterable
    {
        yield 'too wide' => [[1000, 1], 'Unexpected non-positive calculated height'];
        yield 'too tall' => [[1, 1000], 'Unexpected non-positive calculated width'];
    }
}
