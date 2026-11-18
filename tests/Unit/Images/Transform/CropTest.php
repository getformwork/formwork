<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Crop;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImagesFixture;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Crop::class)]
final class CropTest extends TestCase
{
    public function testAreaStartingFromTheOriginIsPreserved(): void
    {
        $gdImage = imagecreatetruecolor(4, 4);
        imagesetpixel($gdImage, 2, 1, 0xFF0000);

        $gdImage = (new Crop(2, 1, 2, 3))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([2, 3], [imagesx($gdImage), imagesy($gdImage)]);
        $this->assertSame(0xFF0000, imagecolorat($gdImage, 0, 0));
    }

    public function testTransparencyIsPreserved(): void
    {
        // The fixture has a transparent 10x10 corner
        $gdImage = (new Crop(0, 0, 20, 20))->apply(GdImagesFixture::image(), new ImageInfo(['hasAlphaChannel' => true]));

        $this->assertSame(127, imagecolorat($gdImage, 2, 2) >> 24);
        $this->assertSame(0, imagecolorat($gdImage, 15, 15) >> 24);
    }

    #[DataProvider('invalidAreaProvider')]
    public function testAreaMustBeValid(int $originX, int $originY, int $width, int $height, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new Crop($originX, $originY, $width, $height);
    }

    /**
     * @return iterable<string, array{int, int, int, int, string}>
     */
    public static function invalidAreaProvider(): iterable
    {
        yield 'negative x origin' => [-1, 0, 1, 1, '$originX must be greater than or equal to 0'];
        yield 'negative y origin' => [0, -1, 1, 1, '$originY must be greater than or equal to 0'];
        yield 'zero width' => [0, 0, 0, 1, '$width must be greater than 0'];
        yield 'zero height' => [0, 0, 1, 0, '$height must be greater than 0'];
    }
}
