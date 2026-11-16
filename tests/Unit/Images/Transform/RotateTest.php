<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Rotate;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Rotate::class)]
final class RotateTest extends TestCase
{
    public function testImagesAreRotatedCounterclockwise(): void
    {
        $gdImage = imagecreatetruecolor(4, 2);
        imagesetpixel($gdImage, 0, 0, 0xFF0000);

        $gdImage = (new Rotate(90))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        // The top left corner is moved to the bottom left
        $this->assertSame([2, 4], [imagesx($gdImage), imagesy($gdImage)]);
        $this->assertSame(0xFF0000, imagecolorat($gdImage, 0, 3));
    }

    public function testUncoveredAreasAreTransparent(): void
    {
        $gdImage = (new Rotate(45))->apply(imagecreatetruecolor(10, 10), new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([15, 15], [imagesx($gdImage), imagesy($gdImage)]);
        $this->assertSame(127, imagecolorat($gdImage, 0, 0) >> 24);
    }
}
